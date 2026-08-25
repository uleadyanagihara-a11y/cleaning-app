<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    members: {
        type: Object,
        required: true,
    },
    cleaningRoles: {
        type: /** @type {import('vue').PropType<Array<{
            id: number,
            name: string,
        }>>} */ (Array),
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    counts: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const showCreateModal = ref(false);
const createForm = useForm(
    /** @type {{
     * name: string,
     * cleaning_role_ids: number[],
     * notes: string,
     * is_active: boolean,
     * }} */ ({
        name: '',
        cleaning_role_ids: [],
        notes: '',
        is_active: true,
    }),
);

const hasFilters = computed(
    () => search.value.trim() !== '' || status.value !== '',
);
const successMessage = computed(() => page.flash.success ?? '');
const cleaningRoleError = computed(() => {
    const error = Object.entries(createForm.errors).find(
        ([key]) =>
            key === 'cleaning_role_ids' ||
            key.startsWith('cleaning_role_ids.'),
    );

    return error?.[1];
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const requestCreateModalClose = () => {
    if (!createForm.processing) {
        closeCreateModal();
    }
};

const submitCreateForm = () => {
    createForm.post(route('members.store'), {
        preserveScroll: true,
        onSuccess: closeCreateModal,
    });
};

const applyFilters = () => {
    router.get(
        route('members.index'),
        {
            search: search.value.trim() || undefined,
            status: status.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    search.value = '';
    status.value = '';
    applyFilters();
};

/**
 * @param {string} label
 * @returns {string}
 */
const paginationLabel = (label) => {
    if (label.includes('Previous')) {
        return '前へ';
    }

    if (label.includes('Next')) {
        return '次へ';
    }

    return label;
};
</script>

<template>
    <Head title="メンバー一覧" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        メンバー一覧
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        登録済みメンバーと担当可能な掃除内容を確認できます。
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    @click="openCreateModal"
                >
                    メンバー登録
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

                <div class="grid grid-cols-3 gap-3 sm:gap-4">
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-gray-500 sm:text-sm">
                            すべて
                        </p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">
                            {{ counts.all }}
                            <span class="text-sm font-normal text-gray-500">名</span>
                        </p>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-gray-500 sm:text-sm">
                            有効
                        </p>
                        <p class="mt-1 text-2xl font-semibold text-emerald-700">
                            {{ counts.active }}
                            <span class="text-sm font-normal text-gray-500">名</span>
                        </p>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-gray-500 sm:text-sm">
                            無効
                        </p>
                        <p class="mt-1 text-2xl font-semibold text-gray-600">
                            {{ counts.inactive }}
                            <span class="text-sm font-normal text-gray-500">名</span>
                        </p>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form
                        class="border-b border-gray-200 p-4 sm:p-6"
                        @submit.prevent="applyFilters"
                    >
                        <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_14rem_auto] sm:items-end">
                            <div>
                                <label
                                    for="member-search"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    メンバー名
                                </label>
                                <input
                                    id="member-search"
                                    v-model="search"
                                    type="search"
                                    maxlength="100"
                                    placeholder="氏名を入力"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>

                            <div>
                                <label
                                    for="member-status"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    状態
                                </label>
                                <select
                                    id="member-status"
                                    v-model="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">すべて</option>
                                    <option value="active">有効</option>
                                    <option value="inactive">無効</option>
                                </select>
                            </div>

                            <div class="flex gap-2">
                                <button
                                    type="submit"
                                    class="inline-flex flex-1 items-center justify-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:flex-none"
                                >
                                    検索
                                </button>
                                <button
                                    v-if="hasFilters"
                                    type="button"
                                    class="inline-flex flex-1 items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:flex-none"
                                    @click="resetFilters"
                                >
                                    クリア
                                </button>
                            </div>
                        </div>
                    </form>

                    <div
                        v-if="members.data.length === 0"
                        class="px-6 py-16 text-center"
                    >
                        <p class="text-base font-medium text-gray-700">
                            {{ hasFilters
                                ? '条件に一致するメンバーはいません。'
                                : '登録済みのメンバーはいません。' }}
                        </p>
                        <button
                            v-if="hasFilters"
                            type="button"
                            class="mt-3 text-sm font-medium text-indigo-600 hover:text-indigo-500"
                            @click="resetFilters"
                        >
                            検索条件をクリア
                        </button>
                    </div>

                    <template v-else>
                        <div class="hidden overflow-x-auto md:block">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            メンバー名
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            担当可能な掃除
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            備考
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            状態
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr
                                        v-for="member in members.data"
                                        :key="member.id"
                                        :class="{ 'bg-gray-50/70': !member.is_active }"
                                    >
                                        <th scope="row" class="whitespace-nowrap px-6 py-4 text-left text-sm font-semibold text-gray-900">
                                            {{ member.name }}
                                        </th>
                                        <td class="max-w-md px-6 py-4">
                                            <div
                                                v-if="member.available_cleaning_roles.length > 0"
                                                class="flex flex-wrap gap-1.5"
                                            >
                                                <span
                                                    v-for="role in member.available_cleaning_roles"
                                                    :key="role.id"
                                                    class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700"
                                                >
                                                    {{ role.name }}
                                                </span>
                                            </div>
                                            <span v-else class="text-sm text-gray-400">
                                                未設定
                                            </span>
                                        </td>
                                        <td class="max-w-sm whitespace-pre-line wrap-break-word px-6 py-4 text-sm text-gray-600">
                                            {{ member.notes || '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                                :class="member.is_active
                                                    ? 'bg-emerald-100 text-emerald-800'
                                                    : 'bg-gray-200 text-gray-700'"
                                            >
                                                {{ member.is_active ? '有効' : '無効' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="divide-y divide-gray-200 md:hidden">
                            <article
                                v-for="member in members.data"
                                :key="member.id"
                                class="p-4"
                                :class="{ 'bg-gray-50': !member.is_active }"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="font-semibold text-gray-900">
                                        {{ member.name }}
                                    </h3>
                                    <span
                                        class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="member.is_active
                                            ? 'bg-emerald-100 text-emerald-800'
                                            : 'bg-gray-200 text-gray-700'"
                                    >
                                        {{ member.is_active ? '有効' : '無効' }}
                                    </span>
                                </div>

                                <dl class="mt-4 space-y-3 text-sm">
                                    <div>
                                        <dt class="font-medium text-gray-500">
                                            担当可能な掃除
                                        </dt>
                                        <dd class="mt-1">
                                            <div
                                                v-if="member.available_cleaning_roles.length > 0"
                                                class="flex flex-wrap gap-1.5"
                                            >
                                                <span
                                                    v-for="role in member.available_cleaning_roles"
                                                    :key="role.id"
                                                    class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700"
                                                >
                                                    {{ role.name }}
                                                </span>
                                            </div>
                                            <span v-else class="text-gray-400">未設定</span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-gray-500">備考</dt>
                                        <dd class="mt-1 whitespace-pre-line wrap-break-word text-gray-700">
                                            {{ member.notes || '—' }}
                                        </dd>
                                    </div>
                                </dl>
                            </article>
                        </div>

                        <div class="flex flex-col gap-4 border-t border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <p class="text-sm text-gray-600">
                                全{{ members.total }}件中
                                {{ members.from }}〜{{ members.to }}件を表示
                            </p>
                            <nav
                                v-if="members.last_page > 1"
                                class="flex flex-wrap gap-1"
                                aria-label="メンバー一覧のページネーション"
                            >
                                <template
                                    v-for="link in members.links"
                                    :key="link.label"
                                >
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-md border px-3 py-2 text-sm font-medium transition"
                                        :class="link.active
                                            ? 'border-indigo-600 bg-indigo-600 text-white'
                                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                                        :aria-current="link.active ? 'page' : undefined"
                                        preserve-scroll
                                    >
                                        {{ paginationLabel(link.label) }}
                                    </Link>
                                    <span
                                        v-else
                                        class="inline-flex min-h-10 min-w-10 cursor-not-allowed items-center justify-center rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-400"
                                    >
                                        {{ paginationLabel(link.label) }}
                                    </span>
                                </template>
                            </nav>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <Modal
            :show="showCreateModal"
            :closeable="!createForm.processing"
            max-width="2xl"
            aria-labelledby="create-member-title"
            @close="requestCreateModalClose"
        >
            <form @submit.prevent="submitCreateForm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h2
                        id="create-member-title"
                        class="text-lg font-semibold text-gray-900"
                    >
                        メンバー登録
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        メンバー情報と担当可能な掃除内容を入力してください。
                    </p>
                </div>

                <div class="space-y-6 px-6 py-5">
                    <div>
                        <InputLabel for="member-name">
                            メンバー名
                            <span class="text-red-600">*</span>
                        </InputLabel>
                        <TextInput
                            id="member-name"
                            v-model="createForm.name"
                            type="text"
                            maxlength="100"
                            autocomplete="name"
                            required
                            autofocus
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="createForm.errors.name"
                        />
                    </div>

                    <fieldset>
                        <legend class="text-sm font-medium text-gray-700">
                            担当可能な掃除
                            <span class="font-normal text-gray-500">（任意）</span>
                        </legend>
                        <div
                            v-if="cleaningRoles.length > 0"
                            class="mt-2 grid gap-2 rounded-md border border-gray-200 p-3 sm:grid-cols-2"
                        >
                            <label
                                v-for="role in cleaningRoles"
                                :key="role.id"
                                class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                <input
                                    v-model="createForm.cleaning_role_ids"
                                    type="checkbox"
                                    :value="role.id"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                />
                                <span>{{ role.name }}</span>
                            </label>
                        </div>
                        <p
                            v-else
                            class="mt-2 rounded-md bg-gray-50 px-3 py-3 text-sm text-gray-500"
                        >
                            選択できる掃除内容がありません。
                        </p>
                        <InputError
                            class="mt-2"
                            :message="cleaningRoleError"
                        />
                    </fieldset>

                    <div>
                        <InputLabel for="member-notes">
                            備考
                            <span class="font-normal text-gray-500">（任意）</span>
                        </InputLabel>
                        <textarea
                            id="member-notes"
                            v-model="createForm.notes"
                            rows="4"
                            maxlength="2000"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="勤務可能な曜日や時間帯など"
                        />
                        <div class="mt-1 flex items-start justify-between gap-3">
                            <InputError :message="createForm.errors.notes" />
                            <span class="ml-auto text-xs text-gray-500">
                                {{ createForm.notes.length }}/2000
                            </span>
                        </div>
                    </div>

                    <fieldset>
                        <legend class="text-sm font-medium text-gray-700">
                            状態 <span class="text-red-600">*</span>
                        </legend>
                        <div class="mt-2 flex flex-wrap gap-5">
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                <input
                                    v-model="createForm.is_active"
                                    type="radio"
                                    :value="true"
                                    class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                />
                                有効
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                <input
                                    v-model="createForm.is_active"
                                    type="radio"
                                    :value="false"
                                    class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                />
                                無効
                            </label>
                        </div>
                        <InputError
                            class="mt-2"
                            :message="createForm.errors.is_active"
                        />
                    </fieldset>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <SecondaryButton
                        type="button"
                        :disabled="createForm.processing"
                        @click="requestCreateModalClose"
                    >
                        キャンセル
                    </SecondaryButton>
                    <PrimaryButton
                        type="submit"
                        :disabled="createForm.processing"
                        :class="{ 'opacity-25': createForm.processing }"
                    >
                        {{ createForm.processing ? '登録中…' : '登録する' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
