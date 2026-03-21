<?php
// /tmp/fix_supabase_rls.php

// 1. Manually specify credentials from .env to avoid dependency issues in tmp
$host = 'aws-1-ap-south-1.pooler.supabase.com';
$port = '6543';
$dbname = 'postgres';
$user = 'postgres.oxhuzfqvlpntlymdwfiy';
$pass = 'HvuTuyenSinh2026';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERR_CODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "Connected to Supabase successfully.\n";

    // Check if table exists
    $stmt = $pdo->prepare("SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE  table_schema = 'public'
        AND    table_name   = 'online_tracking'
    );");
    $stmt->execute();
    if (!$stmt->fetchColumn()) {
        die("Error: Table 'public.online_tracking' not found.\n");
    }

    echo "Found 'online_tracking' table. Applying RLS fix...\n";

    // 1. Enable RLS
    $pdo->exec("ALTER TABLE public.online_tracking ENABLE ROW LEVEL SECURITY;");
    echo "1. RLS Enabled.\n";

    // 2. Drop existing policy if any
    $pdo->exec("DROP POLICY IF EXISTS \"Allow anonymous inserts\" ON public.online_tracking;");
    
    // 3. Create policy for anonymous inserts
    $sql = "CREATE POLICY \"Allow anonymous inserts\" 
            ON public.online_tracking 
            FOR INSERT 
            TO anon 
            WITH CHECK (true);";
    $pdo->exec($sql);
    echo "2. Policy 'Allow anonymous inserts' created.\n";

    // 4. Verify RLS status
    $stmt = $pdo->prepare("SELECT relrowsecurity FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace WHERE n.nspname = 'public' AND c.relname = 'online_tracking';");
    $stmt->execute();
    $rlsStatus = $stmt->fetchColumn();
    
    if ($rlsStatus) {
        echo "\nSUCCESS: RLS is now active and protected on 'online_tracking'.\n";
    } else {
        echo "\nERROR: RLS activation failed.\n";
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
