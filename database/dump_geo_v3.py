import openpyxl

wb = openpyxl.load_workbook("d:/xampp/htdocs/TS/database/geodata_new.xlsx", data_only=True)
sheet = wb.active
print(f"Sheet Name: '{sheet.title}'")

for i, row in enumerate(sheet.iter_rows(min_row=1, max_row=100, values_only=True)):
    print(f"Row {i+1}: {row}")
