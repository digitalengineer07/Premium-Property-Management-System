"""Exercise real handlers on the clone-only local test server; never production."""
import base64, http.cookiejar, json, re, urllib.request, urllib.parse, urllib.error
from pathlib import Path

BASE = 'http://127.0.0.1:8091/'
fixture = json.loads((Path(__file__).parent.parent / '.rollout-backups/http-fixture.json').read_text())
checks = 0
def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def request(opener, path, data=None, token=None, as_json=False):
    headers = {}
    if token: headers['Authorization'] = 'Bearer ' + token
    if data is not None:
        if as_json:
            headers['Content-Type'] = 'application/json'
            data = json.dumps(data).encode()
        else: data = urllib.parse.urlencode(data).encode()
    try:
        response = opener.open(urllib.request.Request(BASE + path, data=data, headers=headers), timeout=30)
        return response.status, response.read().decode('utf-8', errors='replace')
    except urllib.error.HTTPError as e: return e.code, e.read().decode('utf-8', errors='replace')
def check(ok, label):
    global checks
    checks += 1
    if not ok: raise AssertionError(label)
def hidden(html, name):
    match = re.search(r'name="'+name+r'"[^>]*value="([^"]*)"', html)
    if not match: raise AssertionError('Missing form field '+name)
    return match.group(1)
def login(opener, path, username):
    _, html = request(opener, path)
    status, html = request(opener, path, {'username':username,'password':fixture['password'],'csrf':hidden(html,'csrf')})
    check(status==200 and 'Invalid admin' not in html and 'Fatal error' not in html, 'Web login '+path)
    return html

admin = client(); login(admin, 'admin/login.php', fixture['admin'])
for path in ['admin/dashboard.php','admin/manage-payments.php','admin/master-ledger.php','admin/reports.php','admin/monthly-report.php','admin/bill-generator.php','admin/electricity-list.php']:
    status, html=request(admin,path)
    check(status==200 and not re.search(r'(Fatal error|Parse error|Warning(?:</b>)?:|Deprecated(?:</b>)?:)',html), 'Admin page '+path)
for endpoint in ['kpi','revenue_chart','distribution_donut','receivables_aging','resident_performance','electricity_insights','electricity_bar','top_defaulters','anomalies','expense_donut','recent_activity','ai_insights']:
    status, body=request(admin,'admin/api_reports_saas.php?endpoint='+endpoint+'&range=all_time')
    result=json.loads(body)
    check(status==200 and not (isinstance(result,dict) and 'error' in result), 'Report '+endpoint)
for endpoint in ['summary','timeseries','aging','delinquent','revenue_split','usage_bar','months']:
    status, body=request(admin,'admin/api_reports.php?endpoint='+endpoint)
    result=json.loads(body)
    check(status==200 and not (isinstance(result,dict) and 'error' in result), 'Shared report '+endpoint)

_, html=request(admin,'admin/bill-generator.php'); csrf=hidden(html,'csrf') if 'name="csrf"' in html else re.search(r"fd.append\('csrf',\s*'([^']+)'",html).group(1)
bills={}
for mode, resident in fixture['residents'].items():
    payload={'csrf':csrf,'user_id':resident['id'],'bill_month':'2026-01','bill_date':'2026-02-03','previous_reading':'0','current_reading':'100','rate_per_unit':'8','rent_amount':'10000','maintenance':'500','dues':'0','extra_charges':'0'}
    status, body=request(admin,'admin/save-bill.php',payload); result=json.loads(body)
    check(result.get('success') is True and float(result['total_amount'])==11300, 'Create '+mode+' monthly invoice: '+body)
    bills[mode]=result['bill_id']
    status, body=request(admin,'admin/save-bill.php',payload)
    check(json.loads(body).get('success') is False, 'Duplicate monthly invoice rejected')
    renter=client(); login(renter,'login.php',resident['username'])
    for path in ['renter/dashboard.php','renter/my-bills.php','renter/my-payments.php','renter/electricity-record.php']:
        status,html=request(renter,path)
        check(status==200 and not re.search(r'(Fatal error|Parse error|Warning(?:</b>)?:|Deprecated(?:</b>)?:)',html), 'Resident page '+path)

_, html=request(admin,'admin/electricity-list.php')
payment={'csrf':hidden(html,'csrf'),'payment_request_id':hidden(html,'payment_request_id'),'type':'electricity','id':bills['arrears'],'paid_amount':'500.01','payment_mode':'Cash','ajax':'1'}
status,body=request(admin,'admin/mark-paid.php',payment)
check(status==200 and json.loads(body).get('success') is True,'AJAX partial payment returns JSON')
status,body=request(admin,'admin/mark-paid.php',payment)
check(status==200 and 'already recorded' in json.loads(body)['message'],'Double submission does not duplicate cash receipt')

opener=client()
status,body=request(opener,'api/auth/login.php',{'username':fixture['admin'],'password':fixture['password'],'role':'admin'},as_json=True)
token=json.loads(body)['token']; check(status==200 and len(token)==64,'Opaque API token issued')
for path in ['api/admin/dashboard_stats.php','api/admin/get_all_pending.php','api/admin/get_all_electricity.php','api/admin/get_renters_for_billing.php','api/logs/visitor_logs.php']:
    status,body=request(opener,path,token=token); result=json.loads(body)
    check(status==200 and 'error' not in result, 'Authenticated API '+path)
for role in ['admin','resident']:
    forged=base64.b64encode(json.dumps({'id':1,'role':role}).encode()).decode()
    path='api/'+role+'/dashboard_stats.php'
    check(request(opener,path,token=forged)[0]==401,'Forged identity rejected '+role)
    check(request(opener,path)[0]==401,'Anonymous request rejected '+role)
resident=fixture['residents']['arrears']
status,body=request(opener,'api/auth/login.php',{'username':resident['username'],'password':fixture['password'],'role':'resident'},as_json=True)
resident_token=json.loads(body)['token']
check(request(opener,'api/admin/mobile_save_bill.php',{},resident_token,True)[0]==401,'Resident cannot create bills')
status,body=request(opener,'api/resident/dashboard_stats.php',token=resident_token)
check(status==200 and json.loads(body).get('status')=='success','Resident API balance')
check(json.loads(body)['data']['kpis']['total_due']==10799.99,'Resident API agrees with partial-payment ledger')
request(opener,'api/auth/logout.php',{},resident_token,True)
check(request(opener,'api/resident/dashboard_stats.php',token=resident_token)[0]==401,'Logged-out token revoked')
print(f'{checks} HTTP checks passed')
