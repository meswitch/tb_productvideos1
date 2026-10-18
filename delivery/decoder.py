#!/usr/bin/env python3
"""Self-verifying Base64 -> ZIP decoder for the product_video module delivery.

Usage:
    python decode.py pv.txt product_video.zip

Reads a text file containing Base64 (any line wrapping / CRLF / stray whitespace
is tolerated), decodes it, writes the binary output, then prints size + hashes
and validates the ZIP so you can confirm nothing was corrupted in copy-paste.
"""
import sys, base64, hashlib, zlib, io, zipfile

def main():
    if len(sys.argv) != 3:
        print(__doc__)
        sys.exit(1)
    src, dst = sys.argv[1], sys.argv[2]

    raw = open(src, 'rb').read().decode('ascii', 'ignore')
    # Strip everything that is not valid Base64 alphabet or padding
    b64 = ''.join(ch for ch in raw if ch.isalnum() or ch in '+/=')
    b64 = b64.replace('=', '')                       # drop all padding...
    if len(b64) % 4:                                 # ...then re-pad correctly
        b64 += '=' * (4 - len(b64) % 4)

    data = base64.b64decode(b64)
    open(dst, 'wb').write(data)

    print("bytes   :", len(data))
    print("MD5     :", hashlib.md5(data).hexdigest())
    print("SHA256  :", hashlib.sha256(data).hexdigest())
    print("ADLER32 :", format(zlib.adler32(data), '08x'))
    try:
        z = zipfile.ZipFile(io.BytesIO(data))
        bad = z.testzip()
        names = z.namelist()
        print("files   :", len(names), "| integrity:", "OK" if bad is None else "CORRUPT " + bad)
        for n in names:
            print("   ", n)
        expected = {"product_video/product_video.php", "product_video/README.md",
                    "product_video/views/templates/admin/video_tab.tpl",
                    "product_video/views/templates/hook/product_video.tpl",
                    "product_video/views/css/product_video.css"}
        if set(names) == expected and bad is None:
            print("RESULT  : SUCCESS - zip matches the v1.2.0 build")
        else:
            print("RESULT  : MISMATCH - re-copy the Base64 chunks")
    except Exception as e:
        print("RESULT  : FAILED - not a valid zip:", e)

if __name__ == "__main__":
    main()
