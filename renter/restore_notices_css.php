<?php
$c = file_get_contents("notices.php");
$target = ".main-content {\n            flex: 1; margin-left: 230px; padding: 32px 40px; max-width: calc(100% - 230px); box-sizing: border-box;\n        }";
// Replace with regex to avoid line ending mismatches
$pattern = '/\.main-content\s*\{\s*flex:\s*1;\s*margin-left:\s*230px;\s*padding:\s*32px\s*40px;\s*max-width:\s*calc\(100%\s*-\s*230px\);\s*box-sizing:\s*border-box;\s*\}/s';

$replacement = ".main-content {
            flex: 1; margin-left: 230px; padding: 32px 40px; max-width: calc(100% - 230px); box-sizing: border-box;
        }
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .header-greeting h1 { font-size: 28px; font-weight: 800; margin-bottom: 4px; color: var(--text-dark); display: flex; align-items: center; gap: 8px; letter-spacing: -1px; }
        .header-greeting p { font-size: 14px; color: var(--text-gray); font-weight: 500; margin: 0;}
        .header-greeting p span { background: rgba(98, 75, 255, 0.08); color: var(--primary-purple); padding: 2px 8px; border-radius: 6px; font-weight: 600; font-size: 12px; border: 1px solid rgba(98,75,255,0.1); }
        
        .header-actions { display: flex; align-items: center; gap: 16px; }
        .header-actions .icon-btn {
            width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--border); background: white;
            display: flex; align-items: center; justify-content: center; color: var(--text-dark); font-size: 20px;
            position: relative; cursor: pointer; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .header-actions .icon-btn:hover { background: #f8fafc; transform: translateY(-1px); }
        
        .page-btn {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); background: white;
            display: flex; align-items: center; justify-content: center; color: var(--text-dark); font-size: 14px; font-weight: 600;
            cursor: pointer; text-decoration: none; transition: 0.2s;
        }";

$c = preg_replace($pattern, $replacement, $c, 1);
file_put_contents("notices.php", $c);
echo "Restored notices CSS successfully\n";
?>
