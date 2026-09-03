<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
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
            assignment_count: number,
            available_member_count: number,
            can_delete: boolean,
        }>>} */ (Array),
        required: true,
    },
});

const page = usePage();
const showRoleModal = ref(false);
const selectedRole = ref(null);
const roleForm = useForm({
    name: '',
    description: '',
    required_member_count: 1,
    is_active: true,
});
const showDeleteModal = ref(false);
const roleBeingDeleted = ref(null);
const deleteForm = useForm({});

const successMessage = computed(() => page.flash.success ?? '');
const errorMessage = computed(() => page.flash.error ?? '');
const isEditing = computed(() => selectedRole.value !== null);

const openRoleModal = () => {
    selectedRole.value = null;
    roleForm.reset();
    roleForm.clearErrors();
    showRoleModal.value = true;
};

const openEditModal = (role) => {
    selectedRole.value = role;
    roleForm.name = role.name;
    roleForm.description = role.description ?? '';
    roleForm.required_member_count = role.required_member_count;
    roleForm.is_active = role.is_active;
    roleForm.clearErrors();
    showRoleModal.value = true;
};

const closeRoleModal = () => {
    showRoleModal.value = false;
    selectedRole.value = null;
    roleForm.reset();
    roleForm.clearErrors();
};

const requestRoleModalClose = () => {
    if (!roleForm.processing) {
        closeRoleModal();
    }
};

const submitRoleForm = () => {
    const options = {
        preserveScroll: true,
        onSuccess: closeRoleModal,
    };

    if (selectedRole.value) {
        roleForm.patch(
            route('cleaning-items.update', selectedRole.value.id),
            options,
        );

        return;
    }

    roleForm.post(route('cleaning-items.store'), options);
};

const openDeleteModal = (role) => {
    roleBeingDeleted.value = role;
    deleteForm.clearErrors();
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    roleBeingDeleted.value = null;
    deleteForm.reset();
    deleteForm.clearErrors();
};

const requestDeleteModalClose = () => {
    if (!deleteForm.processing) {
        closeDeleteModal();
    }
};

const deleteRole = () => {
    if (!roleBeingDeleted.value?.can_delete) {
        return;
    }

    deleteForm.delete(
        route('cleaning-items.destroy', roleBeingDeleted.value.id),
        {
            preserveScroll: true,
            onSuccess: closeDeleteModal,
        },
    );
};
</script>

<template>
    <Head title="掃除役割一覧" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2
                        class="text-xl leading-tight font-semibold text-gray-800"
                    >
                        掃除役割一覧
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        掃除の担当内容、必要人数、作業の説明を確認できます。
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
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
                <div
                    v-if="errorMessage"
                    role="alert"
                    class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800"
                >
                    {{ errorMessage }}
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
                                            class="w-1/4 px-6 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                        >
                                            役割名
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                        >
                                            説明
                                        </th>
                                        <th
                                            scope="col"
                                            class="w-36 px-6 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                        >
                                            必要人数
                                        </th>
                                        <th
                                            scope="col"
                                            class="w-28 px-6 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                        >
                                            状態
                                        </th>
                                        <th
                                            scope="col"
                                            class="w-32 px-6 py-3 text-right text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                        >
                                            操作
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-gray-200 bg-white"
                                >
                                    <tr
                                        v-for="role in cleaningRoles"
                                        :key="role.id"
                                        :class="{
                                            'bg-gray-50/70': !role.is_active,
                                        }"
                                    >
                                        <th
                                            scope="row"
                                            class="px-6 py-4 text-left text-sm font-semibold text-gray-900"
                                        >
                                            {{ role.name }}
                                        </th>
                                        <td
                                            class="px-6 py-4 text-sm wrap-break-word whitespace-pre-line"
                                            :class="
                                                role.description
                                                    ? 'text-gray-600'
                                                    : 'text-gray-400'
                                            "
                                        >
                                            {{
                                                role.description || '説明未設定'
                                            }}
                                        </td>
                                        <td
                                            class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-700"
                                        >
                                            {{ role.required_member_count }}名
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                                :class="
                                                    role.is_active
                                                        ? 'bg-emerald-100 text-emerald-800'
                                                        : 'bg-gray-200 text-gray-700'
                                                "
                                            >
                                                {{
                                                    role.is_active
                                                        ? '有効'
                                                        : '無効'
                                                }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap"
                                        >
                                            <button
                                                type="button"
                                                class="text-indigo-600 transition hover:text-indigo-900 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                                                :aria-label="`${role.name}を編集`"
                                                @click="openEditModal(role)"
                                            >
                                                編集
                                            </button>
                                            <button
                                                type="button"
                                                class="ml-4 text-red-600 transition hover:text-red-900 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none"
                                                :aria-label="`${role.name}を削除`"
                                                @click="openDeleteModal(role)"
                                            >
                                                削除
                                            </button>
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
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <h3 class="font-semibold text-gray-900">
                                        {{ role.name }}
                                    </h3>
                                    <span
                                        class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="
                                            role.is_active
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : 'bg-gray-200 text-gray-700'
                                        "
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
                                            class="mt-1 wrap-break-word whitespace-pre-line"
                                            :class="
                                                role.description
                                                    ? 'text-gray-600'
                                                    : 'text-gray-400'
                                            "
                                        >
                                            {{
                                                role.description || '説明未設定'
                                            }}
                                        </dd>
                                    </div>
                                </dl>
                                <div
                                    class="mt-4 flex justify-end gap-4 border-t border-gray-200 pt-3 text-sm font-medium"
                                >
                                    <button
                                        type="button"
                                        class="text-indigo-600 hover:text-indigo-900 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                                        @click="openEditModal(role)"
                                    >
                                        編集
                                    </button>
                                    <button
                                        type="button"
                                        class="text-red-600 hover:text-red-900 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none"
                                        @click="openDeleteModal(role)"
                                    >
                                        削除
                                    </button>
                                </div>
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
                        {{ isEditing ? '役割編集' : '役割登録' }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        {{
                            isEditing
                                ? '登録済みの役割情報を変更します。'
                                : '掃除の役割と、その役割に必要な人数を入力してください。'
                        }}
                    </p>
                </div>

                <div class="space-y-6 px-6 py-5">
                    <div
                        v-if="isEditing && selectedRole?.assignment_count > 0"
                        class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800"
                    >
                        この役割には清掃割当が{{
                            selectedRole.assignment_count
                        }}件あります。役割名や説明の変更は過去の割当表示にも反映されます。別の作業として管理する場合は、この役割を無効にして新しい役割を登録してください。
                    </div>
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
                            <span class="font-normal text-gray-500"
                                >（任意）</span
                            >
                        </InputLabel>
                        <textarea
                            id="role-description"
                            v-model="roleForm.description"
                            rows="5"
                            maxlength="2000"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="担当する場所や作業内容など"
                        />
                        <div
                            class="mt-1 flex items-start justify-between gap-3"
                        >
                            <InputError
                                :message="roleForm.errors.description"
                            />
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

                    <fieldset v-if="isEditing">
                        <legend class="text-sm font-medium text-gray-700">
                            状態 <span class="text-red-600">*</span>
                        </legend>
                        <div class="mt-2 flex flex-wrap gap-5">
                            <label
                                class="flex cursor-pointer items-center gap-2 text-sm text-gray-700"
                            >
                                <input
                                    v-model="roleForm.is_active"
                                    type="radio"
                                    :value="true"
                                    class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                />
                                有効
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-2 text-sm text-gray-700"
                            >
                                <input
                                    v-model="roleForm.is_active"
                                    type="radio"
                                    :value="false"
                                    class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                />
                                無効
                            </label>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">
                            無効にすると新しいメンバー設定では選択できなくなります。既存の割当と担当可能メンバーは保持されます。
                        </p>
                        <InputError
                            class="mt-2"
                            :message="roleForm.errors.is_active"
                        />
                    </fieldset>
                </div>

                <div
                    class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4"
                >
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
                        {{
                            roleForm.processing
                                ? isEditing
                                    ? '更新中…'
                                    : '登録中…'
                                : isEditing
                                  ? '更新する'
                                  : '登録する'
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal
            :show="showDeleteModal"
            :closeable="!deleteForm.processing"
            max-width="lg"
            aria-labelledby="delete-role-title"
            @close="requestDeleteModalClose"
        >
            <form @submit.prevent="deleteRole">
                <div class="px-6 py-5">
                    <h2
                        id="delete-role-title"
                        class="text-lg font-semibold text-gray-900"
                    >
                        {{
                            roleBeingDeleted?.can_delete
                                ? '役割を削除しますか？'
                                : 'この役割は削除できません'
                        }}
                    </h2>
                    <p
                        v-if="roleBeingDeleted?.can_delete"
                        class="mt-3 text-sm leading-6 text-gray-600"
                    >
                        <span class="font-semibold text-gray-900">
                            {{ roleBeingDeleted?.name }}
                        </span>
                        を削除します。この操作は元に戻せません。
                    </p>
                    <div
                        v-else
                        class="mt-3 space-y-2 text-sm leading-6 text-gray-600"
                    >
                        <p>
                            <span class="font-semibold text-gray-900">
                                {{ roleBeingDeleted?.name }}
                            </span>
                            は次のデータで使用されています。
                        </p>
                        <ul class="list-disc pl-5">
                            <li v-if="roleBeingDeleted?.assignment_count > 0">
                                清掃割当
                                {{ roleBeingDeleted.assignment_count }}件
                            </li>
                            <li
                                v-if="
                                    roleBeingDeleted?.available_member_count > 0
                                "
                            >
                                担当可能メンバー
                                {{ roleBeingDeleted.available_member_count }}名
                            </li>
                        </ul>
                        <p>
                            使用を終了する場合は、編集から状態を「無効」に変更してください。
                        </p>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4"
                >
                    <SecondaryButton
                        type="button"
                        :disabled="deleteForm.processing"
                        @click="requestDeleteModalClose"
                    >
                        {{
                            roleBeingDeleted?.can_delete
                                ? 'キャンセル'
                                : '閉じる'
                        }}
                    </SecondaryButton>
                    <DangerButton
                        v-if="roleBeingDeleted?.can_delete"
                        type="submit"
                        :disabled="deleteForm.processing"
                        :class="{ 'opacity-25': deleteForm.processing }"
                    >
                        {{ deleteForm.processing ? '削除中…' : '削除する' }}
                    </DangerButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
