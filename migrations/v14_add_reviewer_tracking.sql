-- Add reviewer tracking to ho_so_xet_tuyen
ALTER TABLE public.ho_so_xet_tuyen 
ADD COLUMN nguoi_duyet_id INT REFERENCES public.quan_tri_vien(id);

COMMENT ON COLUMN public.ho_so_xet_tuyen.nguoi_duyet_id IS 'ID của cán bộ thực hiện duyệt hồ sơ';
