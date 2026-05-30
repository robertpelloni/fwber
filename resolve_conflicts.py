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
                    merged[k] = head_json[k] # Fallback
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
        # For lock files, just accept ours or theirs and we can re-install later or just add it
        subprocess.run(['git', 'checkout', '--ours', filepath])
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
            print(f"Failed to merge JSON for {filepath}: {e}")
            # Fallback to text

    with open(filepath, 'r', encoding='utf-8') as f:
        lines = f.readlines()

    out_lines = []
    in_conflict = False
    head_lines = []
    branch_lines = []
    state = 0 # 0: normal, 1: head, 2: branch

    for line in lines:
        if line.startswith('<<<<<<< HEAD') or line.startswith('<<<<<<<'):
            in_conflict = True
            state = 1
            head_lines = []
            branch_lines = []
        elif line.startswith('======='):
            if in_conflict:
                state = 2
            else:
                out_lines.append(line)
        elif line.startswith('>>>>>>>'):
            if in_conflict:
                state = 0
                in_conflict = False
                
                head_text = "".join(head_lines).strip()
                branch_text = "".join(branch_lines).strip()
                
                if 'VERSION' in filepath or filepath.endswith('VERSION'):
                    v1 = parse_version(head_text)
                    v2 = parse_version(branch_text)
                    merged_text = head_text if v1 >= v2 else branch_text
                    out_lines.append(merged_text + '\n')
                else:
                    # Combine unique lines for TS/JS, MD, Prisma, etc.
                    # This preserves both sides but deduplicates exact line matches (like duplicate imports)
                    res = []
                    for l in head_lines + branch_lines:
                        if l not in res:
                            res.append(l)
                    out_lines.extend(res)
            else:
                out_lines.append(line)
        else:
            if state == 1:
                head_lines.append(line)
            elif state == 2:
                branch_lines.append(line)
            else:
                out_lines.append(line)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.writelines(out_lines)

files = get_conflicted_files()
for f in files:
    print(f"Resolving {f}...")
    resolve_file(f)
