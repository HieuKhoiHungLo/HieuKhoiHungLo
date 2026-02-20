import openpyxl

wb = openpyxl.load_workbook("d:/xampp/htdocs/TS/database/geodata_new.xlsx", data_only=True)
sheet = wb.active

count = 0
for i, row in enumerate(sheet.iter_rows(min_row=1, values_only=True)):
    if isinstance(row[1], int):
        print(f"Row {i+1}: {row}")
        count += 1
    if count > 50:
        break
