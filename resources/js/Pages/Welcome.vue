<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-100">
        <div class="rounded-lg bg-white p-10 text-center shadow-md">
            <h1 class="mb-4 text-3xl font-bold">掃除当番管理アプリ</h1>

            <p class="mb-8 text-gray-600">掃除当番をかんたんに管理できます</p>

            <Link
                href="/login"
                class="inline-block rounded-lg bg-blue-600 px-6 py-3 text-white hover:bg-blue-700"
            >
                ログイン
            </Link>
        </div>
    </div>
</template>
