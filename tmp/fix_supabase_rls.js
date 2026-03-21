// /tmp/fix_supabase_rls.js
const { Client } = require('pg');

const config = {
  host: 'aws-1-ap-south-1.pooler.supabase.com',
  port: 6543,
  database: 'postgres',
  user: 'postgres.oxhuzfqvlpntlymdwfiy',
  password: 'HvuTuyenSinh2026',
  ssl: {
    rejectUnauthorized: false // Supabase root CA is usually required, but we can bypass for this script
  }
};

async function run() {
  const client = new Client(config);
  try {
    await client.connect();
    console.log('Connected to Supabase successfully.');

    // 1. Check if table exists
    const checkRes = await client.query(`
      SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE  table_schema = 'public'
        AND    table_name   = 'online_tracking'
      );
    `);
    
    if (!checkRes.rows[0].exists) {
        console.error("Error: Table 'public.online_tracking' not found.");
        process.exit(1);
    }
    console.log("Found 'online_tracking' table.");

    // 2. Enable RLS
    await client.query("ALTER TABLE public.online_tracking ENABLE ROW LEVEL SECURITY;");
    console.log("1. RLS Enabled.");

    // 3. Drop existing policy if any
    await client.query("DROP POLICY IF EXISTS \"Allow anonymous inserts\" ON public.online_tracking;");
    
    // 4. Create policy for anonymous inserts
    const sql = `
      CREATE POLICY "Allow anonymous inserts" 
      ON public.online_tracking 
      FOR INSERT 
      TO anon 
      WITH CHECK (true);
    `;
    await client.query(sql);
    console.log("2. Policy 'Allow anonymous inserts' created.");

    // 5. Verify RLS status
    const verifyRes = await client.query(`
      SELECT relrowsecurity 
      FROM pg_class c 
      JOIN pg_namespace n ON n.oid = c.relnamespace 
      WHERE n.nspname = 'public' AND c.relname = 'online_tracking';
    `);
    
    if (verifyRes.rows[0].relrowsecurity) {
        console.log("\nSUCCESS: RLS is now active and protected on 'online_tracking'.");
    } else {
        console.error("\nERROR: RLS activation failed.");
    }

  } catch (err) {
    console.error('Database Error:', err.message);
  } finally {
    await client.end();
  }
}

run();
