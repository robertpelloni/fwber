import sys
import json
import os
import subprocess
import re

def get_conflicted_files():
    result = subprocess.run(['git', 'diff', '--name-only', '--diff-filter=U'], capture_output=True, text=True)
    return [f for f in result.stdout.split('\n') if f.strip()]

def parse_version(v):
    return tuple(map(int, re.findall(r'\d+', v)))

def merge_json(filepath):
    # For JSON, just use checkout ours for now and run install or similar if it's package.json
    # Or just use the text based fallback which might fail but it's okay.
    pass

def clean_legacy_markers(text):
    text = re.sub(r'<<<<<<< Updated upstream\n?', '', text)
    text = re.sub(r'=======\n?', '', text)
    text = re.sub(r'>>>>>>> Stashed changes\n?', '', text)
    text = re.sub(r'<<<<<<< HEAD\n?', '', text)
    return text

def resolve_file(filepath):
    if not os.path.exists(filepath):
        return
        
    if filepath.endswith('package-lock.json') or filepath.endswith('composer.lock'):
        subprocess.run(['git', 'checkout', '--theirs', filepath])
        return True

    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Regex to match each conflict hunk. 
    # Non-greedy `.*?` ensures we only match one hunk at a time.
    pattern = re.compile(r'<<<<<<< ours\n(.*?)=======\n(.*?)\n?>>>>>>> theirs\n?', re.DOTALL)
    
    def replacer(match):
        ours_text = clean_legacy_markers(match.group(1))
        theirs_text = clean_legacy_markers(match.group(2))
        
        if 'VERSION' in filepath or filepath.endswith('VERSION'):
            v1 = parse_version(ours_text)
            v2 = parse_version(theirs_text)
            return ours_text if v1 >= v2 else theirs_text
            
        if filepath.endswith('.json') or filepath.endswith('.jsonl'):
            # For JSON, try to prefer ours but keep theirs if longer, this is a hack 
            # because valid JSON shouldn't have markers. But since package.json has 
            # line-level conflicts, we just take the longest.
            return ours_text if len(ours_text) >= len(theirs_text) else theirs_text
        
        ours_lines = ours_text.split('\n')
        theirs_lines = theirs_text.split('\n')
        
        res = []
        seen = set()
        for l in ours_lines + theirs_lines:
            s = l.strip()
            if not s:
                if res and res[-1].strip() != '':
                    res.append(l)
                continue
            
            if filepath.endswith('.md'):
                if s not in seen:
                    seen.add(s)
                    res.append(l)
            else:
                if l.startswith('import ') or l.startswith('export '):
                    if s not in seen:
                        seen.add(s)
                        res.append(l)
                else:
                    if s not in seen:
                        seen.add(s)
                        res.append(l)
                    elif s in ['}', ']', ');', '};']:
                        res.append(l)
        
        # Ensure we return with a newline at the end if the chunk had one
        joined = '\n'.join(res)
        return joined + '\n' if joined else ''

    new_content = pattern.sub(replacer, content)
    
    # Just in case there are lingering `<<<<<<< HEAD` from the first bad run, clean them up
    new_content = clean_legacy_markers(new_content)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
    return True

files = get_conflicted_files()
for f in files:
    print(f"Resolving {f}...")
    resolve_file(f)
