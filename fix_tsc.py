import os
import subprocess

def run_cmd(cmd):
    result = subprocess.run(cmd, shell=True, capture_output=True, text=True)
    return result.stdout + result.stderr

out = run_cmd("cd fwber-backend-ts && npx tsc --noEmit")
with open("temp_out.txt", "w") as f:
    f.write(out)
