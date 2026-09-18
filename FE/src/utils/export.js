function giaTriCsv(value) {
  if (value === null || value === undefined) return ''
  return String(value).replace(/"/g, '""')
}

export function taiCsv(filename, columns, rows) {
  const header = columns.map((column) => '"' + giaTriCsv(column.label) + '"').join(',')
  const body = rows.map((row) => columns.map((column) => '"' + giaTriCsv(column.value(row)) + '"').join(',')).join('\r\n')
  const blob = new Blob(['\uFEFF' + header + '\r\n' + body], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.setTimeout(() => URL.revokeObjectURL(url), 1000)
}
