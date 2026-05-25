import sys

def get_file(filepath):
    try:
        with open(filepath, 'r') as f:
            print(f.read()[:500])
    except Exception as e:
        print(e)

get_file('temp_out.txt')
