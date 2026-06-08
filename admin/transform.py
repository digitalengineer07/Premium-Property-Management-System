import sys

with open('c:/xampp/htdocs/renter-system/admin/view-renter.php', 'r', encoding='utf-8') as f:
    text = f.read()

profile_idx = text.find('<div class="panel" style="padding: 0; overflow: hidden;">')
profile_end = text.find('<div class="panel" style="background: var(--bg-main);', profile_idx)

quick_action_idx = text.find('<div class="panel" style="background: var(--bg-main);', profile_idx)
quick_action_end = text.find('</div>\n        </div>\n    </div>\n</main>', quick_action_idx) + 6 # End of panel div

utility_idx = text.find('<div class="panel">\n                <div class="panel-header">\n                    <h2 style="font-size: 18px; font-weight: 700;">Utility History</h2>')
utility_end = text.find('<div class="panel">\n                <div class="panel-header">\n                    <h2 style="font-size: 18px; font-weight: 700;">Rent History</h2>', utility_idx)

rent_idx = text.find('<div class="panel">\n                <div class="panel-header">\n                    <h2 style="font-size: 18px; font-weight: 700;\">Rent History</h2>', utility_idx)
rent_end = text.find('</div>\n        </div>\n\n        <div class="right-col"', rent_idx)

if -1 in [profile_idx, profile_end, quick_action_idx, quick_action_end, utility_idx, utility_end, rent_idx, rent_end]:
    print('Error finding indices!')
    print(profile_idx, profile_end, quick_action_idx, quick_action_end, utility_idx, utility_end, rent_idx, rent_end)
    sys.exit(1)

profile_html = text[profile_idx:profile_end].strip()
quick_action_html = text[quick_action_idx:quick_action_end].strip()
utility_html = text[utility_idx:utility_end].strip()
rent_html = text[rent_idx:rent_end].strip()
# Ensure inner rent_html div closes!
if not rent_html.endswith('</div>\\n        </div>'):
    rent_html += '\n        </div>' # The rent_end cutoff was BEFORE the right-col div wrapper. We need to make sure the panel closes cleanly. Wait, `rent_end` is right before `</div>\n        </div>\n\n        <div class="right-col"`. Actually, the closing divs for the left col.

# Let's verify rent end. 
# rent_html is just the panel. 
rent_panel_end = text.find('</div>\n        </div>\n\n        <div class="right-col"', rent_idx)
# This finds the END of the rent panel which is followed by the end of the left-col.
rent_html = text[rent_idx:rent_panel_end].strip()

# Construct new HTML
new_html = f'''    <div class="dashboard-grid-70 animate-up" style="grid-template-columns: 1fr 1fr; align-items: start; gap: 24px; margin-bottom: 24px;">
        <div class="left-col" style="display: flex; flex-direction: column;">
            {profile_html}
        </div>
        
        <div class="right-col" style="display: flex; flex-direction: column;">
            {quick_action_html}
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px;" class="animate-up">
        {utility_html}
        {rent_html}
    </div>'''

start_full = text.find('<div class="dashboard-grid-70 animate-up" style="grid-template-columns: 2.2fr 1fr; align-items: start;">')
end_full = text.find('</div>\n        </div>\n    </div>\n</main>') + len('</div>\n        </div>\n    </div>')

if start_full == -1:
    print('Error finding start_full')
    sys.exit(1)

final_text = text[:start_full] + new_html + text[end_full:]

with open('c:/xampp/htdocs/renter-system/admin/view-renter.php', 'w', encoding='utf-8') as f:
    f.write(final_text)

print('Done!')
