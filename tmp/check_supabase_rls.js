// /tmp/check_supabase_rls.js
const { Client } = require('pg');

const config = {
  host: 'aws-1-ap-south-1.pooler.supabase.com',
  port: 6543,
  database: 'postgres',
  user: 'postgres.oxhuzfqvlpntlymdwfiy',
  password: 'HvuTuyenSinh2026',
  ssl: { rejectUnauthorized: false }
};

async function run() {
  const client = new Client(config);
  try {
    await client.connect();
    
    // 1. Check RLS status
    const rlsRes = await client.query(`
      SELECT relrowsecurity 
      FROM pg_class c 
      JOIN pg_namespace n ON n.oid = c.relnamespace 
      WHERE n.nspname = 'public' AND c.relname = 'online_tracking';
    `);
    const rlsEnabled = rlsRes.rows[0].relrowsecurity;

    // 2. Check policies
    const policyRes = await client.query(`
      SELECT policyname, roles, cmd, qual 
      FROM pg_policies 
      WHERE tablename = 'online_tracking' AND schemaname = 'public';
    `);

    console.log(JSON.stringify({
      rls_enabled: rlsEnabled,
      policies: policyRes.rows
    }, null, 2));

  } catch (err) {
    console.error('Check Error:', err.message);
  } finally {
    await client.end();
  }
}

run();
