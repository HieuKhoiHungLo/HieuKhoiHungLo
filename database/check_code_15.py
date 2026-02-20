import openpyxl

wb = openpyxl.load_workbook("d:/xampp/htdocs/TS/database/geodata_new.xlsx", data_only=True)
sheet = wb.active

for i, row in enumerate(sheet.iter_rows(min_row=1, values_only=True)):
    if isinstance(row[1], int) and row[2] == '15':
        print(f"Code 15 is: {row[8]}")
        break
