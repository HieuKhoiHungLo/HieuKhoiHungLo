// /tmp/resolve_supabase_v2.js
const { Client } = require('pg');
const config = {
  host: 'aws-1-ap-south-1.pooler.supabase.com',
  port: 6543,
  database: 'postgres',
  user: 'postgres.oxhuzfqvlpntlymdwfiy',
  password: 'HvuTuyenSinh2026',
  ssl: { rejectUnauthorized: false }
};

const tables = [
    "chung_chi_thi_sinh", "diem_nang_khieu", "diem_nang_khieu_ngoai", "diem_thi_thpt",
    "diem_chi_tiet", "admission_scores", "thi_sinh", "ho_so_xet_tuyen",
    "nguyen_vong", "ket_qua_hoc_tap", "email_templates", "email_queue",
    "audit_logs", "login_attempts", "log_import", "quan_tri_vien",
    "password_resets", "notifications", "notification_reads"
];

const sql = "BEGIN; " + tables.map(t => `
    DROP POLICY IF EXISTS "Private: Service role only" ON "public"."${t}";
    CREATE POLICY "Private: Service role only" ON "public"."${t}" FOR ALL TO service_role USING (true);
`).join("\n") + " COMMIT;";

async function run() {
  const client = new Client(config);
  try {
    await client.connect();
    console.log('Connected to Supabase. Running bulk policy creation...');
    await client.query(sql);
    console.log('SUCCESS: All policies created.');
  } catch (err) {
    console.error('Execution Error:', err.message);
    await client.query('ROLLBACK');
  } finally {
    await client.end();
  }
}
run();
