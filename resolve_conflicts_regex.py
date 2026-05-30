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

def merge_json(head_json, branch_json):
    if isinstance(head_json, dict) and isinstance(branch_json, dict):
        merged = {}
        for k in set(head_json.keys()).union(branch_json.keys()):
            if k in head_json and k in branch_json:
                if isinstance(head_json[k], dict) and isinstance(branch_json[k], dict):
                    merged[k] = merge_json(head_json[k], branch_json[k])
                elif isinstance(head_json[k], str) and isinstance(branch_json[k], str):
                    if re.search(r'\d+\.\d+', head_json[k]) and re.search(r'\d+\.\d+', branch_json[k]):
                        v1 = parse_version(head_json[k])
                        v2 = parse_version(branch_json[k])
                        merged[k] = head_json[k] if v1 >= v2 else branch_json[k]
                    else:
                        merged[k] = head_json[k] if len(head_json[k]) > len(branch_json[k]) else branch_json[k]
                elif isinstance(head_json[k], list) and isinstance(branch_json[k], list):
                    merged[k] = merge_json(head_json[k], branch_json[k])
                else:
                    merged[k] = head_json[k]
            elif k in head_json:
                merged[k] = head_json[k]
            else:
                merged[k] = branch_json[k]
        return merged
    elif isinstance(head_json, list) and isinstance(branch_json, list):
        res = []
        for item in head_json + branch_json:
            if item not in res:
                res.append(item)
        return res
    else:
        return branch_json

def resolve_file(filepath):
    if not os.path.exists(filepath):
        return
        
    if filepath.endswith('package-lock.json') or filepath.endswith('composer.lock'):
        subprocess.run(['git', 'checkout', '--theirs', filepath])
        return True

    if filepath.endswith('.json') or filepath.endswith('.jsonl'):
        head_content = subprocess.run(['git', 'show', f':2:{filepath}'], capture_output=True, text=True).stdout
        branch_content = subprocess.run(['git', 'show', f':3:{filepath}'], capture_output=True, text=True).stdout
        try:
            head_json = json.loads(head_content) if head_content.strip() else {}
            branch_json = json.loads(branch_content) if branch_content.strip() else {}
            merged = merge_json(head_json, branch_json)
            with open(filepath, 'w', encoding='utf-8') as f:
                json.dump(merged, f, indent=2)
            return True
        except Exception as e:
            pass # Fallback to regex

    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # The branch name
    branch_name = "origin/feat/federation-hardening-auth-integration-v2.0.14-15931202088087633320"
    
    # We will use a function to process hunks. Since git merge markers don't nest FROM THE SAME MERGE,
    # we can just find `<<<<<<< HEAD` up to the first `=======`, and then up to `>>>>>>> branch_name`.
    # Any internal `<<<<<<< Updated upstream` are just literal text we should strip out.
    
    # First, let's strip out any legacy conflict markers from the branch side that were checked in.
    # This prevents them from ruining the syntax.
    def clean_legacy_markers(text):
        text = re.sub(r'<<<<<<< Updated upstream\n', '', text)
        text = re.sub(r'=======\n', '', text)
        text = re.sub(r'>>>>>>> Stashed changes\n', '', text)
        return text

    pattern = re.compile(r'<<<<<<< HEAD\n(.*?)=======\n(.*?)\n>>>>>>> ' + re.escape(branch_name) + r'\n?', re.DOTALL)
    
    def replacer(match):
        head_text = match.group(1)
        branch_text = clean_legacy_markers(match.group(2))
        
        if 'VERSION' in filepath or filepath.endswith('VERSION'):
            v1 = parse_version(head_text)
            v2 = parse_version(branch_text)
            return head_text if v1 >= v2 else branch_text
        
        head_lines = head_text.split('\n')
        branch_lines = branch_text.split('\n')
        
        res = []
        seen = set()
        for l in head_lines + branch_lines:
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
        
        return '\n'.join(res) + '\n'

    new_content = pattern.sub(replacer, content)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
    return True

files = get_conflicted_files()
for f in files:
    print(f"Resolving {f}...")
    resolve_file(f)
