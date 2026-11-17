<?php
// admin/api_reports.php
require_once "../db.php";
session_start();

header('Content-Type: application/json');

$is_authorized = false;
$auth_token = $_GET['token'] ?? null;

if (isset($_SESSION['admin'])) {
    $is_authorized = true;
} elseif ($auth_token) {
    $qAuth = "SELECT id FROM shared_reports WHERE token = ? AND expires_at > NOW()";
    $stmtAuth = mysqli_prepare($conn, $qAuth);
    if ($stmtAuth) {
        mysqli_stmt_bind_param($stmtAuth, "s", $auth_token);
        mysqli_stmt_execute($stmtAuth);
        $resAuth = mysqli_stmt_get_result($stmtAuth);
        if (mysqli_fetch_assoc($resAuth)) {
            $is_authorized = true;
        }
        mysqli_stmt_close($stmtAuth);
    }
}

if (!$is_authorized) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$endpoint = $_GET['endpoint'] ?? 'summary';
require_once __DIR__ . '/../includes/accounting_reports.php';
if (in_array($endpoint, ['share','schedule'], true) && (!isset($_SESSION['admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')))) { http_response_code(403); echo json_encode(['error'=>'Admin POST and CSRF token required']); exit; }
$month_filter = isset($_GET['month']) && !empty($_GET['month']) && $_GET['month'] !== 'all' ? mysqli_real_escape_string($conn, $_GET['month']) : null;

$rent_where = $month_filter ? " AND month='$month_filter'" : "";
$elec_where = $month_filter ? " AND month='$month_filter'" : "";
$elec_alias_where = $month_filter ? " AND e.month='$month_filter'" : "";

try {
    switch ($endpoint) {
        
        case 'summary':
            $r = accounting_report($conn, $month_filter);
            echo json_encode(['total_rent_collected'=>$r['applied']['rent_amount']/100,'electricity_collected'=>$r['applied']['amount']/100,
                'outstanding_amount'=>$r['net_paise']/100,'renters_due'=>$r['due_residents'],'electricity_profit'=>null,
                'cash_received'=>$r['cash_paise']/100,'deposit_receipts'=>$r['deposit_receipts_paise']/100]);
            break;
        case 'timeseries':
            $data=[];
            foreach (array_slice(accounting_report_periods($conn),-12) as $period) {
                if ($month_filter && accounting_month($month_filter)!==$period) continue;
                $r=accounting_report($conn,$period);
                $data[]=['month'=>$period,'rent'=>$r['applied']['rent_amount']/100,'electricity'=>$r['applied']['amount']/100];
            }
            echo json_encode($data); break;
        case 'aging':
            $r=accounting_report($conn,$month_filter); $ages=[0,0,0,0,0];
            foreach($r['invoices'] as $i) {
                if (!isset($r['active_users'][$i['user_id']]) || ($month_filter && $i['month']!==accounting_month($month_filter))) continue;
                $days=$i['due_date'] ? (int)floor((strtotime(date('Y-m-d'))-strtotime($i['due_date']))/86400) : 0;
                $bucket=$days<=7 ? 0 : ($days<=30 ? 1 : ($days<=60 ? 2 : ($days<=90 ? 3 : 4)));
                $ages[$bucket]+=$i['due_paise'];
            }
            $labels=['Current / 0–7 Days','8–30 Days','31–60 Days','61–90 Days','90+ Days']; $data=[];
            foreach($ages as $i=>$value) $data[]=['bracket'=>$labels[$i],'amount'=>$value/100];
            echo json_encode($data); break;
        case 'delinquent':
            $data=[]; $users=accounting_query($conn,"SELECT id,name,room_no FROM users WHERE status='active'");
            while($u=mysqli_fetch_assoc($users)) {
                $due=accounting_balances($conn,(int)$u['id'],$month_filter)['net_paise'];
                if ($due>0) $data[]=['id'=>(int)$u['id'],'name'=>htmlspecialchars($u['name'],ENT_QUOTES,'UTF-8'),'room_no'=>htmlspecialchars($u['room_no']),'total_due'=>$due/100];
            }
            usort($data,fn($a,$b)=>$b['total_due'] <=> $a['total_due']); echo json_encode(array_slice($data,0,10)); break;

        case 'anomalies':
            // Find electricity bills where the current usage is > 50% higher than the previous month's usage
            $query = "
                SELECT e1.id, u.name, u.room_no, e1.month, (e1.current_reading - e1.previous_reading) as units
                FROM electricity e1
                JOIN users u ON e1.user_id = u.id
                WHERE (e1.current_reading - e1.previous_reading) > 250 " . str_replace("month", "e1.month", $elec_where) . "
                ORDER BY units DESC LIMIT 5
            ";
            $res = mysqli_query($conn, $query);
            $anoms = [];
            while($r = mysqli_fetch_assoc($res)) {
                $anoms[] = $r;
            }
            echo json_encode($anoms);
            break;

        case 'revenue_split':
            $r=accounting_report($conn,$month_filter);
            echo json_encode(['rent'=>$r['applied']['rent_amount']/100,'electricity'=>$r['applied']['amount']/100]); break;

        case 'usage_bar':
            // Top 5 rooms by electricity usage overall
            $query = "
                SELECT u.room_no, u.name, SUM(e.current_reading - e.previous_reading) as total_units
                FROM electricity e
                JOIN users u ON e.user_id = u.id
                WHERE 1=1 $elec_alias_where
                GROUP BY u.id
                ORDER BY total_units DESC LIMIT 5
            ";
            $res = mysqli_query($conn, $query);
            $usage = [];
            while($r = mysqli_fetch_assoc($res)) {
                $usage[] = [
                    'label' => "Room " . $r['room_no'],
                    'units' => (int)$r['total_units']
                ];
            }
            echo json_encode($usage);
            break;

        case 'months':
            $res = mysqli_query($conn, "SELECT DISTINCT month FROM rent UNION SELECT DISTINCT month FROM electricity ORDER BY month DESC");
            $all_months = [];
            while($m = mysqli_fetch_array($res)) {
                $all_months[] = $m[0];
            }
            echo json_encode($all_months);
            break;

        case 'share':
            // Generate a secure token
            $token = bin2hex(random_bytes(16));
            // Expires in 24 hours
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $admin_name = $_SESSION['admin'] ?? 'Admin';
            
            $q = "INSERT INTO shared_reports (token, expires_at, created_by) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $q);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sss", $token, $expires, $admin_name);
                if (mysqli_stmt_execute($stmt)) {
                    $link = "https://" . $_SERVER['HTTP_HOST'] . "/admin/shared-report.php?token=" . $token;
                    echo json_encode(['success' => true, 'link' => $link]);
                } else {
                    echo json_encode(['error' => 'Could not generate link']);
                }
                mysqli_stmt_close($stmt);
            } else {
                echo json_encode(['error' => 'Database error']);
            }
            break;

        case 'schedule':
            $data = json_decode(file_get_contents('php://input'), true);
            $email = $data['email'] ?? '';
            $freq = $data['frequency'] ?? 'Weekly';
            
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $q = "INSERT INTO report_schedules (email, frequency) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $q);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ss", $email, $freq);
                    if (mysqli_stmt_execute($stmt)) {
                        echo json_encode(['success' => true, 'message' => 'Report scheduled for ' . $email]);
                    } else {
                        echo json_encode(['error' => 'Could not schedule report']);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    echo json_encode(['error' => 'Database error']);
                }
            } else {
                echo json_encode(['error' => 'Invalid email address']);
            }
            break;

        default:
            echo json_encode(['error' => 'Invalid endpoint']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage()); echo json_encode(['error' => 'Unable to calculate report.']);
}
