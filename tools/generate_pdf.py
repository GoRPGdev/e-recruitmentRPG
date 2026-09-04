import os
import sys
import re
import subprocess
import markdown

EDGE_PATHS = [
    r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe",
    r"C:\Program Files\Microsoft\Edge\Application\msedge.exe",
]

def get_edge_path():
    for p in EDGE_PATHS:
        if os.path.exists(p):
            return p
    return None

def preprocess_markdown(text):
    lines = text.splitlines()
    out = []
    for i, line in enumerate(lines):
        m_sub = re.match(r'^(\s+)([-\*]|\d+\.)\s+(.*)$', line)
        if m_sub:
            indent_len = len(m_sub.group(1))
            marker = m_sub.group(2)
            rest = m_sub.group(3)
            level = 1 if indent_len < 4 else (2 if indent_len < 8 else 3)
            norm_indent = '    ' * level
            if i > 0 and lines[i-1].strip() != '' and not lines[i-1].startswith(' ' * (level * 4)):
                out.append('')
            out.append(f'{norm_indent}{marker} {rest}')
        else:
            m_top = re.match(r'^([-\*]|\d+\.)\s+(.*)$', line)
            if m_top:
                if i > 0 and lines[i-1].strip() != '' and not re.match(r'^([-\*]|\d+\.)\s+', lines[i-1]):
                    out.append('')
                out.append(line)
            else:
                out.append(line)
    return '\n'.join(out)

def build_html(md_content, title="Dokumen Laporan RPG"):
    clean_md = preprocess_markdown(md_content)

    body_html = markdown.markdown(
        clean_md,
        extensions=["tables", "fenced_code"]
    )

    body_html = re.sub(
        r'<h3>Informasi Ringkas</h3>\s*<ul>(.*?)</ul>',
        r'<div class="info-card"><div class="info-title">Informasi Ringkas</div><ul class="info-list">\1</ul></div>',
        body_html,
        flags=re.DOTALL
    )

    body_html = re.sub(
        r'<h3>Informasi Dokumen</h3>\s*<ul>(.*?)</ul>',
        r'<div class="info-card"><div class="info-title">Informasi Dokumen</div><ul class="info-list">\1</ul></div>',
        body_html,
        flags=re.DOTALL
    )

    full_html = f"""<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{title}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=IBM+Plex+Mono:wght@400;500&display=swap');

  @page {{
    size: A4 portrait;
    margin: 20mm 18mm 20mm 18mm;
  }}

  :root {{
    --primary: #1b5e4a;
    --primary-dark: #123d30;
    --primary-light: #e8f4ef;
    --text: #1e293b;
    --text-muted: #64748b;
    --border: #e2e8f0;
    --border-card: #cbd5e1;
    --bg-card: #f8fafc;
  }}

  * {{
    box-sizing: border-box;
  }}

  body {{
    font-family: 'IBM Plex Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--text);
    background-color: #ffffff;
    line-height: 1.6;
    font-size: 10pt;
    margin: 0;
    padding: 0;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }}

  .running-header {{
    position: fixed;
    top: -14mm;
    left: 0;
    right: 0;
    height: 9mm;
    border-bottom: 1.5px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 8pt;
    color: var(--text-muted);
  }}

  .running-header-brand {{
    font-weight: 700;
    color: var(--primary);
  }}

  .running-footer {{
    position: fixed;
    bottom: -13mm;
    left: 0;
    right: 0;
    height: 8mm;
    border-top: 1.5px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 8pt;
    color: var(--text-muted);
  }}

  .brand-badge {{
    font-size: 8pt;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--primary);
    background: var(--primary-light);
    padding: 3px 8px;
    border-radius: 4px;
    display: inline-block;
    margin-bottom: 8px;
  }}

  h1 {{
    font-family: 'Archivo', sans-serif;
    font-size: 18pt;
    font-weight: 800;
    color: var(--primary-dark);
    margin: 0 0 6px 0;
    line-height: 1.25;
  }}

  h1 + h3, h1 + h2 {{
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: 11pt;
    font-weight: 500;
    color: var(--text-muted);
    margin: 0 0 14px 0;
  }}

  h2 {{
    font-family: 'Archivo', sans-serif;
    font-size: 12pt;
    font-weight: 700;
    color: var(--primary-dark);
    margin: 20px 0 10px 0;
    border-left: 4px solid var(--primary);
    padding-left: 8px;
    page-break-after: avoid;
    break-after: avoid;
  }}

  p {{
    margin: 0 0 10px 0;
    text-align: justify;
  }}

  ol, ul {{
    margin: 0 0 12px 0;
    padding-left: 22px;
  }}

  li {{
    margin-bottom: 6px;
    text-align: justify;
    page-break-inside: avoid;
    break-inside: avoid;
  }}

  li > p {{
    margin: 0 0 4px 0;
  }}

  hr {{
    border: none;
    border-top: 1px solid var(--border);
    margin: 16px 0;
  }}

  .info-card {{
    background: var(--bg-card);
    border: 1px solid var(--border-card);
    border-left: 4px solid var(--primary);
    border-radius: 6px;
    padding: 10px 14px;
    margin: 14px 0;
    page-break-inside: avoid;
    break-inside: avoid;
  }}

  .info-title {{
    font-family: 'Archivo', sans-serif;
    font-weight: 700;
    font-size: 9.5pt;
    color: var(--primary-dark);
    margin-bottom: 6px;
  }}

  .info-list {{
    margin: 0;
    padding-left: 18px;
  }}

  .info-list li {{
    margin-bottom: 3px;
    font-size: 9.2pt;
  }}

  code {{
    font-family: 'IBM Plex Mono', monospace;
    font-size: 8.5pt;
    background-color: #f1f5f9;
    color: #0f172a;
    padding: 1.5px 4px;
    border-radius: 3px;
  }}

  @media print {{
    body {{
      background: white;
    }}
    .info-card, li {{
      page-break-inside: avoid;
      break-inside: avoid;
    }}
    h1, h2 {{
      page-break-after: avoid;
      break-after: avoid;
    }}
  }}
</style>
</head>
<body>
  <div class="running-header">
    <span class="running-header-brand">RATU PERTIWI GROUP &bull; E-RECRUITMENT RPG</span>
    <span>Laporan Ringkas Progres Sistem</span>
  </div>

  <div class="running-footer">
    <span>Internal &amp; Rahasia RPG &bull; UU PDP No. 27/2022</span>
    <span>4 September 2026</span>
  </div>

  <div class="brand-badge">RINGKASAN EKSEKUTIF RPG</div>
  <div class="content">
    {body_html}
  </div>
</body>
</html>
"""
    return full_html

def convert_md_to_pdf(input_md, output_pdf=None, output_html=None):
    if not os.path.exists(input_md):
        print(f"Error: {input_md} tidak ditemukan!")
        return False

    base_name = os.path.splitext(input_md)[0]
    if not output_pdf:
        output_pdf = base_name + ".pdf"
    if not output_html:
        output_html = base_name + ".html"

    print(f"Membaca: {input_md}")
    with open(input_md, "r", encoding="utf-8") as f:
        md_text = f.read()

    title_match = re.search(r'^#\s+(.+)$', md_text, re.MULTILINE)
    doc_title = title_match.group(1) if title_match else "Laporan RPG"

    html_content = build_html(md_text, title=doc_title)
    with open(output_html, "w", encoding="utf-8") as f:
        f.write(html_content)

    edge_bin = get_edge_path()
    if not edge_bin:
        print("Error: Microsoft Edge tidak ditemukan!")
        return False

    cmd = [
        edge_bin,
        "--headless",
        "--disable-gpu",
        "--run-all-compositor-stages-before-draw",
        "--no-pdf-header-footer",
        f"--print-to-pdf={output_pdf}",
        f"file:///{output_html.replace(os.sep, '/')}"
    ]

    res = subprocess.run(cmd, capture_output=True, text=True)
    if res.returncode != 0:
        print(f"Edge error: {res.stderr}")
        return False

    if os.path.exists(output_pdf):
        print(f"SUKSES: {output_pdf} ({os.path.getsize(output_pdf)} bytes)")
        return True
    return False

if __name__ == "__main__":
    if len(sys.argv) > 1:
        target = os.path.abspath(sys.argv[1])
    else:
        target = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "docs", "LAPORAN_PROGRES_RINGKAS.md"))

    convert_md_to_pdf(target)
