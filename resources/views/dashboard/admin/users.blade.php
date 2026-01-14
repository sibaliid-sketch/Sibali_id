@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Kelola Pengguna</h1>

        <!-- Search and Filter -->
        <div class="mb-6 flex flex-wrap gap-4">
            <input type="text" placeholder="Cari pengguna..." class="border border-gray-300 rounded px-4 py-2 flex-1 min-w-64">
            <select class="border border-gray-300 rounded px-4 py-2">
                <option value="">Semua Role</option>
                <option value="admin">Admin</option>
                <option value="staff">Staff</option>
                <option value="teacher">Teacher</option>
                <option value="student">Student</option>
                <option value="parent">Parent</option>
            </select>
            <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Cari</button>
        </div>

        <!-- Users Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Nama</th>
                        <th class="px-4 py-2 text-left">Email</th>
                        <th class="px-4 py-2 text-left">Role</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- TODO: Loop through users -->
                    <tr class="border-t">
                        <td colspan="5" class="px-4 py-4 text-center text-gray-500">Belum ada data pengguna</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            <!-- TODO: Add pagination -->
        </div>
    </div>
</div>
@endsection
