<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white p-10 rounded-lg shadow-md text-center">
            <h1 class="text-3xl font-bold mb-4">
                掃除当番管理アプリ
            </h1>

            <p class="text-gray-600 mb-8">
                掃除当番をかんたんに管理できます
            </p>

            <Link
                href="/login"
                class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700"
            >
                ログイン
            </Link>
        </div>
    </div>
</template>
