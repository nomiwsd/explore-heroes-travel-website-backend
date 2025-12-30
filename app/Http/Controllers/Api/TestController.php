<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class TestController extends Controller
{
    /**
     * Test database connection and show tables
     * GET /api/test/database
     */
    public function testDatabase()
    {
        try {
            // Test connection
            DB::connection()->getPdo();

            $dbName = DB::connection()->getDatabaseName();

            // Get all tables
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . $dbName;

            $tableList = array_map(function($table) use ($tableKey) {
                return $table->$tableKey;
            }, $tables);

            // Get table counts
            $tableCounts = [];
            foreach ($tableList as $table) {
                try {
                    $count = DB::table($table)->count();
                    $tableCounts[$table] = $count;
                } catch (\Exception $e) {
                    $tableCounts[$table] = 'Error: ' . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Database connection successful!',
                'database' => $dbName,
                'host' => config('database.connections.mysql.host'),
                'username' => config('database.connections.mysql.username'),
                'total_tables' => count($tableList),
                'tables' => $tableList,
                'table_row_counts' => $tableCounts
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database connection failed!',
                'error' => $e->getMessage(),
                'database' => config('database.connections.mysql.database'),
                'host' => config('database.connections.mysql.host'),
            ], 500);
        }
    }

    /**
     * Get data from specific table
     * GET /api/test/table/{tableName}
     */
    public function getTableData($tableName, Request $request)
    {
        try {
            $limit = $request->get('limit', 10);

            // Check if table exists
            $dbName = DB::connection()->getDatabaseName();
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . $dbName;

            $tableExists = false;
            foreach ($tables as $table) {
                if ($table->$tableKey === $tableName) {
                    $tableExists = true;
                    break;
                }
            }

            if (!$tableExists) {
                return response()->json([
                    'success' => false,
                    'message' => "Table '$tableName' does not exist",
                ], 404);
            }

            // Get table structure
            $columns = DB::select("SHOW COLUMNS FROM `$tableName`");

            // Get data
            $data = DB::table($tableName)->limit($limit)->get();
            $total = DB::table($tableName)->count();

            return response()->json([
                'success' => true,
                'table' => $tableName,
                'total_rows' => $total,
                'showing' => $data->count(),
                'limit' => $limit,
                'columns' => $columns,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching table data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users data (common test)
     * GET /api/test/users
     */
    public function getUsers(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);

            $users = DB::table('users')
                ->select('id', 'name', 'email', 'created_at', 'updated_at')
                ->limit($limit)
                ->get();

            $total = DB::table('users')->count();

            return response()->json([
                'success' => true,
                'message' => 'Users fetched successfully',
                'total_users' => $total,
                'showing' => $users->count(),
                'limit' => $limit,
                'data' => $users
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bookings data
     * GET /api/test/bookings
     */
    public function getBookings(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);

            $bookings = DB::table('bravo_bookings')
                ->limit($limit)
                ->get();

            $total = DB::table('bravo_bookings')->count();

            return response()->json([
                'success' => true,
                'message' => 'Bookings fetched successfully',
                'total_bookings' => $total,
                'showing' => $bookings->count(),
                'limit' => $limit,
                'data' => $bookings
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching bookings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Health check
     * GET /api/test/health
     */
    public function health()
    {
        return response()->json([
            'success' => true,
            'message' => 'API is working!',
            'timestamp' => now(),
            'database' => config('database.connections.mysql.database'),
            'environment' => config('app.env'),
        ], 200);
    }
}
