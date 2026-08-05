import os

path = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-approvals_desktop.php'

css_to_add = """
        /* Notification Dropdown Fix */
        .notification-wrapper { position: relative; }
        #notifDropdown { 
            position: absolute; 
            top: 110%; 
            right: 0; 
            width: 340px; 
            background: white; 
            border-radius: 16px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.15); 
            border: 1px solid var(--border); 
            z-index: 99999; 
            overflow: hidden; 
            text-align: left;
        }
        .dark-theme #notifDropdown {
            background: var(--sidebar-bg);
            border-color: var(--border);
        }
"""

if os.path.exists(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Inject before </style>
    content = content.replace("</style>", css_to_add + "\n    </style>")
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Added notifDropdown CSS successfully.")
