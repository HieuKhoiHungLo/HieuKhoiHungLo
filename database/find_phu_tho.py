import openpyxl

wb = openpyxl.load_workbook("d:/xampp/htdocs/TS/database/geodata_new.xlsx", data_only=True)
sheet = wb.active

for i, row in enumerate(sheet.iter_rows(min_row=1, values_only=True)):
    if any(isinstance(c, str) and "Phú Thọ" in c for c in row):
        print(f"Row {i+1}: {row}")
        # Stop after first few matches
        if i > 5000: break
