<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    cleaningRoles: {
        type: /** @type {import('vue').PropType<Array<{
            id: number,
            name: string,
            description: string|null,
            is_active: boolean,
        }>>} */ (Array),
        required: true,
    },
});
</script>

<template>
    <Head title="掃除役割一覧" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    掃除役割一覧
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    掃除の担当内容と作業の説明を確認できます。
                </p>
            </div>
        </template>

        <div class="py-8 sm:py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div
                        v-if="cleaningRoles.length === 0"
                        class="px-6 py-16 text-center"
                    >
                        <p class="text-base font-medium text-gray-700">
                            登録済みの掃除役割はありません。
                        </p>
                    </div>

                    <template v-else>
                        <div class="hidden overflow-x-auto md:block">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            scope="col"
                                            class="w-1/4 px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                        >
                                            役割名
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                        >
                                            説明
                                        </th>
                                        <th
                                            scope="col"
                                            class="w-28 px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                        >
                                            状態
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr
                                        v-for="role in cleaningRoles"
                                        :key="role.id"
                                        :class="{ 'bg-gray-50/70': !role.is_active }"
                                    >
                                        <th
                                            scope="row"
                                            class="px-6 py-4 text-left text-sm font-semibold text-gray-900"
                                        >
                                            {{ role.name }}
                                        </th>
                                        <td
                                            class="whitespace-pre-line wrap-break-word px-6 py-4 text-sm"
                                            :class="role.description
                                                ? 'text-gray-600'
                                                : 'text-gray-400'"
                                        >
                                            {{ role.description || '説明未設定' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                                :class="role.is_active
                                                    ? 'bg-emerald-100 text-emerald-800'
                                                    : 'bg-gray-200 text-gray-700'"
                                            >
                                                {{ role.is_active ? '有効' : '無効' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="divide-y divide-gray-200 md:hidden">
                            <article
                                v-for="role in cleaningRoles"
                                :key="role.id"
                                class="p-4"
                                :class="{ 'bg-gray-50': !role.is_active }"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="font-semibold text-gray-900">
                                        {{ role.name }}
                                    </h3>
                                    <span
                                        class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="role.is_active
                                            ? 'bg-emerald-100 text-emerald-800'
                                            : 'bg-gray-200 text-gray-700'"
                                    >
                                        {{ role.is_active ? '有効' : '無効' }}
                                    </span>
                                </div>
                                <p
                                    class="mt-3 whitespace-pre-line wrap-break-word text-sm"
                                    :class="role.description
                                        ? 'text-gray-600'
                                        : 'text-gray-400'"
                                >
                                    {{ role.description || '説明未設定' }}
                                </p>
                            </article>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
