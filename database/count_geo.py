import openpyxl

wb = openpyxl.load_workbook("d:/xampp/htdocs/TS/database/geodata_new.xlsx", data_only=True)
sheet = wb.active

provinces = set()
districts = set()
wards_count = 0

for i, row in enumerate(sheet.iter_rows(min_row=1, values_only=True)):
    if isinstance(row[1], int):
        ma_tinh = row[2]
        ma_huyen = row[3]
        if ma_tinh: provinces.add(ma_tinh)
        if ma_huyen: districts.add(ma_huyen)
        wards_count += 1

print(f"Total Provinces: {len(provinces)}")
print(f"Total Districts: {len(districts)}")
print(f"Total Wards: {wards_count}")
print(f"Provinces: {sorted(list(provinces))}")
