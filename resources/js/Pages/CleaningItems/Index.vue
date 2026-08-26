<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    cleaningRoles: {
        type: /** @type {import('vue').PropType<Array<{
            id: number,
            name: string,
            description: string|null,
            required_member_count: number,
            is_active: boolean,
        }>>} */ (Array),
        required: true,
    },
});

const page = usePage();
const showRoleModal = ref(false);
const roleForm = useForm({
    name: '',
    description: '',
    required_member_count: 1,
});

const successMessage = computed(() => page.flash.success ?? '');

const openRoleModal = () => {
    roleForm.reset();
    roleForm.clearErrors();
    showRoleModal.value = true;
};

const closeRoleModal = () => {
    showRoleModal.value = false;
    roleForm.reset();
    roleForm.clearErrors();
};

const requestRoleModalClose = () => {
    if (!roleForm.processing) {
        closeRoleModal();
    }
};

const submitRoleForm = () => {
    roleForm.post(route('cleaning-items.store'), {
        preserveScroll: true,
        onSuccess: closeRoleModal,
    });
};
</script>

<template>
    <Head title="掃除役割一覧" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        掃除役割一覧
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        掃除の担当内容、必要人数、作業の説明を確認できます。
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    @click="openRoleModal"
                >
                    役割登録
                </button>
            </div>
        </template>

        <div class="py-8 sm:py-12">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="successMessage"
                    role="status"
                    class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
                >
                    {{ successMessage }}
                </div>
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
                                            class="w-36 px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                        >
                                            必要人数
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
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-700">
                                            {{ role.required_member_count }}名
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
                                <dl class="mt-3 space-y-3 text-sm">
                                    <div>
                                        <dt class="font-medium text-gray-500">
                                            必要人数
                                        </dt>
                                        <dd class="mt-1 text-gray-700">
                                            {{ role.required_member_count }}名
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-gray-500">
                                            説明
                                        </dt>
                                        <dd
                                            class="mt-1 whitespace-pre-line wrap-break-word"
                                            :class="role.description
                                                ? 'text-gray-600'
                                                : 'text-gray-400'"
                                        >
                                            {{ role.description || '説明未設定' }}
                                        </dd>
                                    </div>
                                </dl>
                            </article>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <Modal
            :show="showRoleModal"
            :closeable="!roleForm.processing"
            max-width="2xl"
            aria-labelledby="role-form-title"
            @close="requestRoleModalClose"
        >
            <form @submit.prevent="submitRoleForm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h2
                        id="role-form-title"
                        class="text-lg font-semibold text-gray-900"
                    >
                        役割登録
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        掃除の役割と、その役割に必要な人数を入力してください。
                    </p>
                </div>

                <div class="space-y-6 px-6 py-5">
                    <div>
                        <InputLabel for="role-name">
                            役割名
                            <span class="text-red-600">*</span>
                        </InputLabel>
                        <TextInput
                            id="role-name"
                            v-model="roleForm.name"
                            type="text"
                            maxlength="100"
                            required
                            autofocus
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="roleForm.errors.name"
                        />
                    </div>

                    <div>
                        <InputLabel for="role-description">
                            説明
                            <span class="font-normal text-gray-500">（任意）</span>
                        </InputLabel>
                        <textarea
                            id="role-description"
                            v-model="roleForm.description"
                            rows="5"
                            maxlength="2000"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="担当する場所や作業内容など"
                        />
                        <div class="mt-1 flex items-start justify-between gap-3">
                            <InputError :message="roleForm.errors.description" />
                            <span class="ml-auto text-xs text-gray-500">
                                {{ roleForm.description.length }}/2000
                            </span>
                        </div>
                    </div>

                    <div>
                        <InputLabel for="role-required-member-count">
                            必要人数
                            <span class="text-red-600">*</span>
                        </InputLabel>
                        <div class="mt-1 flex items-center gap-2">
                            <input
                                id="role-required-member-count"
                                v-model.number="roleForm.required_member_count"
                                type="number"
                                min="1"
                                max="99"
                                step="1"
                                required
                                class="block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <span class="text-sm text-gray-600">名</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            1～99名で入力してください。
                        </p>
                        <InputError
                            class="mt-2"
                            :message="roleForm.errors.required_member_count"
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <SecondaryButton
                        type="button"
                        :disabled="roleForm.processing"
                        @click="requestRoleModalClose"
                    >
                        キャンセル
                    </SecondaryButton>
                    <PrimaryButton
                        type="submit"
                        :disabled="roleForm.processing"
                        :class="{ 'opacity-25': roleForm.processing }"
                    >
                        {{ roleForm.processing ? '登録中…' : '登録する' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
