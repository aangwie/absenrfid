import pathlib, zipfile
base = pathlib.Path(r'd:\PROYEK LARAVEL\absenrfid\tmp_import_test')
base.mkdir(exist_ok=True)

def esc(v):
    return str(v).replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;')

def sheet_rows(rows):
    sd = ''
    for i, r in enumerate(rows, start=1):
        sd += f'<row r=\"{i}\">'
        for c, v in enumerate(r):
            sd += f'<c r=\"{chr(65 + c)}{i}\" t=\"inlineStr\"><is><t>{esc(v)}</t></is></c>'
        sd += '</row>'
    return sd

rows = [
    ['NISN', 'RFID_TAG', 'NAMA', 'KELAS', 'GENDER'],
    ['0091111111', 'TAGMC01', 'Siswa MC Test', 'XII RPL 1', 'L'],
    ['0092222222', 'TAGMC02', 'Siswi MC Test', 'XII RPL 2', 'P'],
]

# File 1: sheet bawa mc:Ignorable tanpa xmlns:mc (reproduksi error user, file Excel/LibreOffice asli)
sheet_mc = ('<?xml version=\"1.0\" encoding=\"UTF-8\"?>'
    '<worksheet xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\" '
    'mc:Ignorable=\"x14ac x16xe\">'
    f'<sheetData>{sheet_rows(rows)}</sheetData></worksheet>')
# File 2: sheet normal sharedStrings (tipe s)
ss = ('<?xml version=\"1.0\" encoding=\"UTF-8\"?>'
    '<sst xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\" count=\"10\" uniqueCount=\"10\">'
    + ''.join(f'<si><t>{esc(v)}</t></si>' for r in rows for v in r) + '</sst>')
idx = {}
for i, r in enumerate(rows):
    for c, v in enumerate(r):
        idx[(i, c)] = len(idx)
sd2 = ''
for i, r in enumerate(rows, start=1):
    sd2 += f'<row r=\"{i}\">'
    for c in range(5):
        sd2 += f'<c r=\"{chr(65 + c)}{i}\" t=\"s\"><v>{idx[(i - 1, c)]}</v></c>'
    sd2 += '</row>'
sheet_s = ('<?xml version=\"1.0\" encoding=\"UTF-8\"?>'
    '<worksheet xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\">'
    f'<sheetData>{sd2}</sheetData></worksheet>')

ct = ('<?xml version=\"1.0\" encoding=\"UTF-8\"?><Types xmlns=\"http://schemas.openxmlformats.org/package/2006/content-types\">'
    '<Default Extension=\"rels\" ContentType=\"application/vnd.openxmlformats-package.relationships+xml\"/>'
    '<Default Extension=\"xml\" ContentType=\"application/xml\"/>'
    '<Override PartName=\"/xl/workbook.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml\"/>'
    '<Override PartName=\"/xl/worksheets/sheet1.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>'
    '<Override PartName=\"/xl/styles.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml\"/>'
    '</Types>')
rels = ('<?xml version=\"1.0\" encoding=\"UTF-8\"?><Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\">'
    '<Relationship Id=\"rId1\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument\" Target=\"xl/workbook.xml\"/>'
    '</Relationships>')
wb = ('<?xml version=\"1.0\" encoding=\"UTF-8\"?><workbook xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\" '
    'xmlns:r=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships\">'
    '<sheets><sheet name=\"Siswa\" sheetId=\"1\" r:id=\"rId1\"/></sheets></workbook>')
wrels = ('<?xml version=\"1.0\" encoding=\"UTF-8\"?><Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\">'
    '<Relationship Id=\"rId1\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet\" Target=\"worksheets/sheet1.xml\"/>'
    '<Relationship Id=\"rId2\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles\" Target=\"styles.xml\"/>'
    '</Relationships>')
styles = ('<?xml version=\"1.0\" encoding=\"UTF-8\"?><styleSheet xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\">'
    '<fonts count=\"1\"><font><sz val=\"11\"/><name val=\"Calibri\"/></font></fonts>'
    '<fills count=\"1\"><fill><patternFill patternType=\"none\"/></fill></fills>'
    '<borders count=\"1\"><border/></borders><cellStyleXfs count=\"1\"><xf/></cellStyleXfs>'
    '<cellXfs count=\"1\"><xf/></cellXfs></styleSheet>')

def make(path, sheet, shared=None):
    with zipfile.ZipFile(path, 'w', zipfile.ZIP_DEFLATED) as z:
        z.writestr('[Content_Types].xml', ct)
        z.writestr('_rels/.rels', rels)
        z.writestr('xl/workbook.xml', wb)
        z.writestr('xl/_rels/workbook.xml.rels', wrels)
        z.writestr('xl/worksheets/sheet1.xml', sheet)
        z.writestr('xl/styles.xml', styles)
        if shared:
            z.writestr('xl/sharedStrings.xml', shared)

make(base / 'mc_broken.xlsx', sheet_mc)
make(base / 'shared_ok.xlsx', sheet_s, ss)
print('test files ready')
