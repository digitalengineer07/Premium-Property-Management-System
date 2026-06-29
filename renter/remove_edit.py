import re

with open('profile.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Remove edit button from Residence Details
old_res = r"""<h3>Residence Details</h3>\s*<button class="btn-outline" onclick="alert\('Edit form would open here\.'\)"><i class='bx bx-edit-alt'></i> Edit</button>"""
new_res = """<h3>Residence Details</h3>"""
content = re.sub(old_res, new_res, content)

# I should also make sure there wasn't a different onclick handler since I updated it to open a modal... wait!
# Did I add the modal to Residence Details?
# Oh, earlier I used a regex that completely replaced Residence Details and its button:
# <button class="btn-outline" onclick="alert('Edit form would open here.')"><i class='bx bx-edit-alt'></i> Edit</button>
# Let's just remove that line entirely in python regardless of the onclick handler.
content = re.sub(r'<h3>Residence Details</h3>\s*<button class="btn-outline" onclick=".*?"><i class=\'bx bx-edit-alt\'></i> Edit</button>', '<h3>Residence Details</h3>', content)


with open('profile.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("Removed edit button from Residence Details")
