const encoder = new TextEncoder()

const crcTable = Array.from({ length: 256 }, (_, index) => {
  let value = index
  for (let bit = 0; bit < 8; bit += 1) {
    value = (value & 1) ? (0xEDB88320 ^ (value >>> 1)) : (value >>> 1)
  }
  return value >>> 0
})

function utf8(value) {
  return encoder.encode(value)
}

function crc32(bytes) {
  let crc = 0xFFFFFFFF
  for (const byte of bytes) crc = crcTable[(crc ^ byte) & 0xFF] ^ (crc >>> 8)
  return (crc ^ 0xFFFFFFFF) >>> 0
}

function noiBytes(chunks) {
  const result = new Uint8Array(chunks.reduce((total, chunk) => total + chunk.length, 0))
  let offset = 0
  chunks.forEach((chunk) => {
    result.set(chunk, offset)
    offset += chunk.length
  })
  return result
}

/** Tạo ZIP không nén theo chuẩn PKZIP; đủ cho gói OpenXML .xlsx. */
function taoZip(files) {
  const localChunks = []
  const centralChunks = []
  let localOffset = 0
  const now = new Date()
  const dosTime = (now.getHours() << 11) | (now.getMinutes() << 5) | Math.floor(now.getSeconds() / 2)
  const dosDate = ((Math.max(now.getFullYear(), 1980) - 1980) << 9) | ((now.getMonth() + 1) << 5) | now.getDate()

  Object.entries(files).forEach(([filename, data]) => {
    const name = utf8(filename)
    const checksum = crc32(data)
    const local = new Uint8Array(30 + name.length + data.length)
    const localView = new DataView(local.buffer)
    localView.setUint32(0, 0x04034B50, true)
    localView.setUint16(4, 20, true)
    localView.setUint16(6, 0x0800, true)
    localView.setUint16(8, 0, true)
    localView.setUint16(10, dosTime, true)
    localView.setUint16(12, dosDate, true)
    localView.setUint32(14, checksum, true)
    localView.setUint32(18, data.length, true)
    localView.setUint32(22, data.length, true)
    localView.setUint16(26, name.length, true)
    localView.setUint16(28, 0, true)
    local.set(name, 30)
    local.set(data, 30 + name.length)
    localChunks.push(local)

    const central = new Uint8Array(46 + name.length)
    const centralView = new DataView(central.buffer)
    centralView.setUint32(0, 0x02014B50, true)
    centralView.setUint16(4, 20, true)
    centralView.setUint16(6, 20, true)
    centralView.setUint16(8, 0x0800, true)
    centralView.setUint16(10, 0, true)
    centralView.setUint16(12, dosTime, true)
    centralView.setUint16(14, dosDate, true)
    centralView.setUint32(16, checksum, true)
    centralView.setUint32(20, data.length, true)
    centralView.setUint32(24, data.length, true)
    centralView.setUint16(28, name.length, true)
    centralView.setUint16(30, 0, true)
    centralView.setUint16(32, 0, true)
    centralView.setUint16(34, 0, true)
    centralView.setUint16(36, 0, true)
    centralView.setUint32(38, 0, true)
    centralView.setUint32(42, localOffset, true)
    central.set(name, 46)
    centralChunks.push(central)
    localOffset += local.length
  })

  const centralDirectory = noiBytes(centralChunks)
  const end = new Uint8Array(22)
  const endView = new DataView(end.buffer)
  endView.setUint32(0, 0x06054B50, true)
  endView.setUint16(4, 0, true)
  endView.setUint16(6, 0, true)
  endView.setUint16(8, centralChunks.length, true)
  endView.setUint16(10, centralChunks.length, true)
  endView.setUint32(12, centralDirectory.length, true)
  endView.setUint32(16, localOffset, true)
  endView.setUint16(20, 0, true)

  return noiBytes([...localChunks, centralDirectory, end])
}

function giaTriO(value) {
  return value === null || value === undefined ? '' : value
}

function tenTrangTinh(filename) {
  const name = String(filename || 'Du lieu')
    .replace(/\.[^.]+$/, '')
    .replace(/[\\/*?:\[\]]/g, ' ')
    .trim()

  return (name || 'Du lieu').slice(0, 31)
}

function doRongCot(column, rows) {
  const lengths = rows.map((row) => String(giaTriO(column.value(row))).length)
  return Math.min(50, Math.max(12, String(column.label).length + 2, ...lengths.map((length) => length + 2)))
}

function escapeXml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;')
}

function tenCot(index) {
  let number = index
  let name = ''

  while (number > 0) {
    number -= 1
    name = String.fromCharCode(65 + (number % 26)) + name
    number = Math.floor(number / 26)
  }

  return name
}

function oXml(value, reference, style = 0) {
  const styleAttribute = style ? ` s="${style}"` : ''

  if (typeof value === 'number' && Number.isFinite(value)) {
    return `<c r="${reference}"${styleAttribute}><v>${value}</v></c>`
  }

  if (typeof value === 'boolean') {
    return `<c r="${reference}"${styleAttribute} t="b"><v>${value ? 1 : 0}</v></c>`
  }

  const text = String(giaTriO(value))
  const preserve = /^\s|\s$/.test(text) ? ' xml:space="preserve"' : ''
  return `<c r="${reference}"${styleAttribute} t="inlineStr"><is><t${preserve}>${escapeXml(text)}</t></is></c>`
}

function taoSheetXml(columns, rows) {
  const lastColumn = tenCot(Math.max(columns.length, 1))
  const lastRow = Math.max(rows.length + 1, 1)
  const dimension = `A1:${lastColumn}${lastRow}`
  const widths = columns.map((column, index) => `<col min="${index + 1}" max="${index + 1}" width="${doRongCot(column, rows)}" customWidth="1"/>`).join('')
  const header = columns.map((column, index) => oXml(column.label, `${tenCot(index + 1)}1`, 1)).join('')
  const dataRows = rows.map((row, rowIndex) => {
    const rowNumber = rowIndex + 2
    const cells = columns.map((column, columnIndex) => oXml(column.value(row), `${tenCot(columnIndex + 1)}${rowNumber}`)).join('')
    return `<row r="${rowNumber}">${cells}</row>`
  }).join('')
  const autoFilter = columns.length ? `<autoFilter ref="${dimension}"/>` : ''

  return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <dimension ref="${dimension}"/>
  <sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>
  <sheetFormatPr defaultRowHeight="20"/>
  <cols>${widths}</cols>
  <sheetData><row r="1" ht="24" customHeight="1">${header}</row>${dataRows}</sheetData>
  ${autoFilter}
</worksheet>`
}

function taoWorkbookXlsx(sheetName, columns, rows) {
  const createdAt = new Date().toISOString()
  const files = {
    '[Content_Types].xml': utf8(`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>`),
    '_rels/.rels': utf8(`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>`),
    'docProps/app.xml': utf8(`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>EduPortal</Application></Properties>`),
    'docProps/core.xml': utf8(`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>EduPortal</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">${createdAt}</dcterms:created></cp:coreProperties>`),
    'xl/workbook.xml': utf8(`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="${escapeXml(sheetName)}" sheetId="1" r:id="rId1"/></sheets></workbook>`),
    'xl/_rels/workbook.xml.rels': utf8(`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>`),
    'xl/styles.xml': utf8(`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2"><font><sz val="11"/><name val="Aptos"/><family val="2"/><scheme val="minor"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Aptos"/><family val="2"/></font></fonts>
  <fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0F766E"/><bgColor indexed="64"/></patternFill></fill></fills>
  <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs>
  <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles><dxfs count="0"/><tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>
</styleSheet>`),
    'xl/worksheets/sheet1.xml': utf8(taoSheetXml(columns, rows)),
  }

  return taoZip(files)
}

/**
 * Tạo file Excel thật để không phụ thuộc dấu phân cách hoặc bảng mã CSV.
 * Giữ tên hàm cũ để toàn bộ màn hình quản trị viên/sinh viên cùng được nâng cấp.
 */
export async function taiCsv(filename, columns, rows) {
  try {
    const archive = taoWorkbookXlsx(tenTrangTinh(filename), columns, rows)
    const blob = new Blob([archive], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = String(filename || 'du-lieu.csv').replace(/\.csv$/i, '.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.setTimeout(() => URL.revokeObjectURL(url), 1000)
  } catch (error) {
    console.error('Không thể xuất file Excel.', error)
    window.alert('Không thể xuất file Excel. Vui lòng thử lại.')
  }
}
