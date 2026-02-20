import openpyxl
import json

wb = openpyxl.load_workbook("d:/xampp/htdocs/TS/database/geodata_new.xlsx", data_only=True)
sheet = wb.active

provinces = {} # ma_tinh -> ten_tinh
districts = {} # ma_huyen -> {name: ten_huyen, p_code: ma_tinh}
wards = []     # {ma_xa, ten_xa, ma_huyen}

current_p_name = None
current_d_name = None

for i, row in enumerate(sheet.iter_rows(min_row=1, values_only=True)):
    # Check for Province Header
    # Province names usually in Col 2 (index 2)
    c2 = row[2]
    c3 = row[3]
    c1 = row[1]
    
    if isinstance(c2, str) and ("Tỉnh" in c2 or "Thành phố" in c2) and c1 is None:
        current_p_name = c2.strip()
        continue
        
    if isinstance(c3, str) and any(k in c3 for k in ["Quận", "Huyện", "Thị xã", "Thành phố"]) and c1 is None:
        current_d_name = c3.strip()
        continue
    
    # Check for Ward Row (STT is integer)
    if isinstance(c1, int):
        ma_tinh = str(row[2]).strip().zfill(2)
        ma_huyen = str(row[3]).strip().zfill(3)
        ma_xa = str(row[4]).strip().zfill(5)
        ten_xa = str(row[5]).strip()
        
        if ma_tinh not in provinces:
            provinces[ma_tinh] = current_p_name
            
        if ma_huyen not in districts:
            districts[ma_huyen] = {"name": current_d_name, "p_code": ma_tinh}
            
        wards.append({
            "ma_xa": ma_xa,
            "ten_xa": ten_xa,
            "ma_huyen": ma_huyen
        })

print(f"Total Provinces: {len(provinces)}")
print(f"Total Districts: {len(districts)}")
print(f"Total Wards: {len(wards)}")

# Save to JSON for verification
with open("d:/xampp/htdocs/TS/database/geodata_parsed.json", "w", encoding="utf-8") as f:
    json.dump({"provinces": provinces, "districts": districts, "wards": wards}, f, ensure_ascii=False, indent=4)
