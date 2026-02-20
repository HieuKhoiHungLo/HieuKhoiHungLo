import openpyxl

wb = openpyxl.load_workbook("d:/xampp/htdocs/TS/database/geodata_new.xlsx", data_only=True)
sheet = wb.active

# Search for "Thành phố" or "Tỉnh" to find where names are
for i, row in enumerate(sheet.iter_rows(min_row=1, max_row=100, values_only=True)):
    # Look for strings that look like province names
    for cell in row:
        if isinstance(cell, str) and ("Thành phố" in cell or "Tỉnh" in cell or "Hà Nội" in cell):
             print(f"Row {i+1} might contain Province: {row}")
             break
    
    # Also print any row that has a non-None value in the first few columns but STT is None
    if row[1] is None and any(row[2:]):
        print(f"Row {i+1} (Header/Info?): {row}")
