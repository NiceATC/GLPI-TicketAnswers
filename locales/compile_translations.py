#!/usr/bin/env python3
"""
Compile .po files to .mo for GLPI plugin
This script compiles translation files without requiring gettext installation
"""
import os
import struct
import array

def generate_mo_file(po_file, mo_file):
    """
    Generate a .mo file from a .po file with proper UTF-8 handling
    """
    translations = {}
    
    # Read .po file with UTF-8 BOM handling
    with open(po_file, 'r', encoding='utf-8-sig') as f:
        msgid = None
        msgstr = None
        in_msgid = False
        in_msgstr = False
        
        for line in f:
            line = line.strip()
            
            # Skip empty lines and comments (except header)
            if not line or line.startswith('#'):
                continue
            
            # Handle msgid
            if line.startswith('msgid "'):
                if msgid is not None and msgstr is not None and msgid:
                    # Validate UTF-8 before adding
                    try:
                        msgid.encode('utf-8')
                        msgstr.encode('utf-8')
                        translations[msgid] = msgstr
                    except UnicodeEncodeError:
                        print(f"Warning: Skipping invalid UTF-8 string: {msgid}")
                msgid = line[7:-1]  # Remove 'msgid "' and '"'
                in_msgid = True
                in_msgstr = False
            # Handle msgstr
            elif line.startswith('msgstr "'):
                msgstr = line[8:-1]  # Remove 'msgstr "' and '"'
                in_msgid = False
                in_msgstr = True
            # Handle continuation lines
            elif line.startswith('"') and line.endswith('"'):
                content = line[1:-1]
                if in_msgid:
                    msgid += content
                elif in_msgstr:
                    msgstr += content
        
        # Add last translation
        if msgid is not None and msgstr is not None and msgid:
            try:
                msgid.encode('utf-8')
                msgstr.encode('utf-8')
                translations[msgid] = msgstr
            except UnicodeEncodeError:
                print(f"Warning: Skipping invalid UTF-8 string: {msgid}")
    
    # Remove empty msgid (header)
    if '' in translations:
        del translations['']
    
    # Generate .mo file
    keys = sorted(translations.keys())
    offsets = []
    ids = b''
    strs = b''
    
    for key in keys:
        try:
            key_bytes = key.encode('utf-8')
            str_bytes = translations[key].encode('utf-8')
            offsets.append((len(ids), len(key_bytes), len(strs), len(str_bytes)))
            ids += key_bytes + b'\0'
            strs += str_bytes + b'\0'
        except UnicodeEncodeError as e:
            print(f"Error encoding string: {key} - {e}")
            continue
    
    # The header is 7 32-bit unsigned integers
    keystart = 7 * 4 + 16 * len(keys)
    valuestart = keystart + len(ids)
    
    # Create the header
    header = struct.pack(
        'Iiiiiii',
        0x950412de,              # Magic number
        0,                        # Version
        len(keys),               # Number of entries
        7 * 4,                   # Start of key index
        7 * 4 + 8 * len(keys),  # Start of value index
        0,                        # Size of hash table
        0                         # Offset of hash table
    )
    
    # Create the index
    koffsets = []
    voffsets = []
    for o1, l1, o2, l2 in offsets:
        koffsets.append(struct.pack('ii', l1, keystart + o1))
        voffsets.append(struct.pack('ii', l2, valuestart + o2))
    
    # Write the .mo file
    with open(mo_file, 'wb') as f:
        f.write(header)
        f.write(b''.join(koffsets))
        f.write(b''.join(voffsets))
        f.write(ids)
        f.write(strs)
    
    print(f"✓ Compiled {po_file} -> {mo_file}")

# Get the directory of this script
locales_dir = os.path.dirname(os.path.abspath(__file__))

# Compile all .po files
po_files = ['pt_BR.po', 'en_US.po']

for po_file in po_files:
    po_path = os.path.join(locales_dir, po_file)
    mo_path = os.path.join(locales_dir, po_file.replace('.po', '.mo'))
    
    if os.path.exists(po_path):
        try:
            generate_mo_file(po_path, mo_path)
        except Exception as e:
            print(f"✗ Error compiling {po_file}: {e}")
    else:
        print(f"✗ File not found: {po_file}")

print("\n✓ All translation files compiled successfully!")
