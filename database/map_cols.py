import openpyxl

wb = openpyxl.load_workbook("d:/xampp/htdocs/TS/database/geodata_new.xlsx", data_only=True)
sheet = wb.active

for i in range(1, 21):
    row = sheet[i]
    vals = [cell.value for cell in row]
    print(f"Row {i}:")
    for j, val in enumerate(vals):
        print(f"  Col {j}: {val}")
