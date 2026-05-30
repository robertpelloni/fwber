import subprocess
print(subprocess.run("git rev-parse --abbrev-ref HEAD", shell=True, capture_output=True, text=True).stdout.strip())
