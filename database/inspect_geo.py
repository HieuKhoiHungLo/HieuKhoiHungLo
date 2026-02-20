import openpyxl

wb = openpyxl.load_workbook("d:/xampp/htdocs/TS/database/geodata_new.xlsx", data_only=True)
sheet = wb.active

print(f"Sheet Name: {sheet.title}")

# Peek at the first 20 rows to find headers
for i, row in enumerate(sheet.iter_rows(max_row=20, values_only=True)):
    print(f"Row {i+1}: {row}")
