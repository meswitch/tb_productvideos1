#!/usr/bin/env python3
"""Decode product_video.zip from pasted text containing CHUNK markers or raw base64.
Usage: python decode.py pasted.txt product_video.zip"""
import sys, base64, hashlib, zipfile
raw = open(sys.argv[1]).read()
# strip chunk header lines and any non-base64 chars
lines = [l.strip() for l in raw.splitlines()]
keep = [l for l in lines if l and not l.startswith('=') and 'CHUNK' not in l
        and not l.startswith(('MD5','SHA256','Size','product_video.zip'))]
b64 = ''.join(''.join(keep).split())
pad = (-len(b64)) % 4
data = base64.b64decode(b64 + '=' * pad)
open(sys.argv[2], 'wb').write(data)
md5 = hashlib.md5(data).hexdigest()
sha = hashlib.sha256(data).hexdigest()
print(f"decoded size : {len(data)}")
print(f"MD5          : {md5}")
print(f"SHA256       : {sha}")
try:
    z = zipfile.ZipFile(sys.argv[2]); bad = z.testzip()
    names = z.namelist()
    print(f"zip entries  : {len(names)} files, integrity {'OK' if bad is None else 'BROKEN'}")
    for n in names: print("  -", n)
except Exception as e:
    print("zip check FAILED:", e); sys.exit(1)
if md5 == '11ee794437bd930008b956877c37155a':
    print("RESULT: SUCCESS - zip matches the v1.2.0 build")
else:
    print("RESULT: MISMATCH - copy was truncated/corrupted, re-copy all 10 chunks")
    sys.exit(1)
