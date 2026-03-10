import sys
import re

def check_html(filename):
    try:
        with open(filename, 'r', encoding='utf-8') as f:
            html = f.read()
    except Exception as e:
        print(f"[{filename}] ERROR reading: {e}")
        return
    
    # Remove PHP blocks first to avoid matching tags inside PHP comments or strings
    html = re.sub(r'<\?php.*?\?>', '', html, flags=re.DOTALL)
    html = re.sub(r'<\?=.*?\?>', '', html, flags=re.DOTALL)
    
    tags = re.findall(r'</?(?:div|span|p|a|ul|li|table|tr|td|th|thead|tbody|label)\b[^>]*>', html, re.IGNORECASE)
    
    stack = []
    
    for tag in tags:
        is_closing = tag.startswith('</')
        tag_name_match = re.search(r'</?([a-zA-Z0-9]+)', tag)
        if not tag_name_match:
            continue
        tag_name = tag_name_match.group(1).lower()
        
        # Self-closing tags 
        if tag.endswith('/>'):
            continue
            
        if not is_closing:
            stack.append((tag_name, tag))
        else:
            if not stack:
                print(f"[{filename}] ERROR: </{tag_name}> but stack is empty!")
            else:
                top_tag, top_full = stack[-1]
                if top_tag == tag_name:
                    stack.pop()
                else:
                    print(f"[{filename}] ERROR: Tag mismatch. Expected </{top_tag}>, found </{tag_name}>. Opening was {top_full}")
                    found = False
                    for i in range(len(stack)-1, -1, -1):
                        if stack[i][0] == tag_name:
                            found = True
                            print(f"[{filename}] RECOVERY: Popped to match </{tag_name}>")
                            stack = stack[:i]
                            break
                    if not found:
                        print(f"[{filename}] RECOVERY FAILED: </{tag_name}> has no matching open tag.")

    if stack:
        print(f"[{filename}] ERROR: Unclosed tags remaining: {[t[0] for t in stack]}")
    else:
        print(f"[{filename}] OK. All checked tags matched.")

for f in [
    'resources/views/admin/review/_tab_personal.php',
    'resources/views/admin/review/personal/_view.php',
    'resources/views/admin/review/personal/_form.php',
    'resources/views/admin/review/personal/_status.php',
    'resources/views/admin/review/_tab_academic.php',
    'resources/views/admin/review/academic/_view.php',
    'resources/views/admin/review/academic/_form.php',
    'resources/views/admin/review/academic/_status.php',
    'resources/views/admin/review/academic/_evidence.php'
]:
    check_html(f)
