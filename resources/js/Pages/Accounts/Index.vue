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
    accounts: { type: Object, required: true },
    filters: { type: Object, required: true },
    counts: { type: Object, required: true },
});

const page = usePage();
const search = ref(props.filters.search ?? '');
const hasFilters = computed(() => search.value.trim() !== '');
const successMessage = computed(() => page.flash.success ?? '');

const showAccountModal = ref(false);
const accountForm = useForm(
    /** @type {{
     * name: string,
     * email: string,
     * password: string,
     * password_confirmation: string,
     * }} */ ({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    }),
);

const openCreateModal = () => {
    accountForm.reset();
    accountForm.clearErrors();
    showAccountModal.value = true;
};

const closeAccountModal = () => {
    showAccountModal.value = false;
    accountForm.reset();
    accountForm.clearErrors();
};

const requestAccountModalClose = () => {
    if (!accountForm.processing) {
        closeAccountModal();
    }
};

const submitAccountForm = () => {
    accountForm.post(route('accounts.store'), {
        preserveScroll: true,
        onSuccess: closeAccountModal,
    });
};

const applyFilters = () => {
    router.get(
        route('accounts.index'),
        {
            search: search.value.trim() || undefined,
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
    applyFilters();
};

/**
 * @param {string|null} date
 * @returns {string}
 */
const formatDate = (date) => {
    if (!date) {
        return '—';
    }

    return new Intl.DateTimeFormat('ja-JP', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(new Date(date));
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
    <Head title="アカウント一覧" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2
                        class="text-xl leading-tight font-semibold text-gray-800"
                    >
                        アカウント一覧
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        システムを利用できるアカウントを確認できます。
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                    @click="openCreateModal"
                >
                    アカウント登録
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
                <div class="grid gap-3 sm:gap-4">
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-gray-500 sm:text-sm">
                            すべて
                        </p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">
                            {{ counts.all }}
                            <span class="text-sm font-normal text-gray-500"
                                >件</span
                            >
                        </p>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <form
                        class="border-b border-gray-200 p-4 sm:p-6"
                        @submit.prevent="applyFilters"
                    >
                        <div
                            class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end"
                        >
                            <div>
                                <label
                                    for="account-search"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    名前・メールアドレス
                                </label>
                                <input
                                    id="account-search"
                                    v-model="search"
                                    type="search"
                                    maxlength="100"
                                    placeholder="名前またはメールアドレスを入力"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>

                            <div class="flex gap-2">
                                <button
                                    type="submit"
                                    class="inline-flex flex-1 items-center justify-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none sm:flex-none"
                                >
                                    検索
                                </button>
                                <button
                                    v-if="hasFilters"
                                    type="button"
                                    class="inline-flex flex-1 items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none sm:flex-none"
                                    @click="resetFilters"
                                >
                                    クリア
                                </button>
                            </div>
                        </div>
                    </form>

                    <div
                        v-if="accounts.data.length === 0"
                        class="px-6 py-16 text-center"
                    >
                        <p class="text-base font-medium text-gray-700">
                            {{
                                hasFilters
                                    ? '条件に一致するアカウントはありません。'
                                    : '登録済みのアカウントはありません。'
                            }}
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
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                        >
                                            名前
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                        >
                                            メールアドレス
                                        </th>
                                        <th
                                            scope="col"
                                            class="w-40 px-6 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                        >
                                            登録日
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-gray-200 bg-white"
                                >
                                    <tr
                                        v-for="account in accounts.data"
                                        :key="account.id"
                                    >
                                        <th
                                            scope="row"
                                            class="px-6 py-4 text-left text-sm font-semibold text-gray-900"
                                        >
                                            <div
                                                class="flex flex-wrap items-center gap-2"
                                            >
                                                <span>{{ account.name }}</span>
                                                <span
                                                    v-if="account.is_current"
                                                    class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700"
                                                >
                                                    自分
                                                </span>
                                            </div>
                                        </th>
                                        <td
                                            class="px-6 py-4 text-sm text-gray-600"
                                        >
                                            <a
                                                :href="`mailto:${account.email}`"
                                                class="break-all hover:text-indigo-600 hover:underline"
                                            >
                                                {{ account.email }}
                                            </a>
                                        </td>
                                        <td
                                            class="px-6 py-4 text-sm whitespace-nowrap text-gray-600"
                                        >
                                            {{ formatDate(account.created_at) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="divide-y divide-gray-200 md:hidden">
                            <article
                                v-for="account in accounts.data"
                                :key="account.id"
                                class="p-4"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <h3
                                                class="font-semibold text-gray-900"
                                            >
                                                {{ account.name }}
                                            </h3>
                                            <span
                                                v-if="account.is_current"
                                                class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700"
                                            >
                                                自分
                                            </span>
                                        </div>
                                        <a
                                            :href="`mailto:${account.email}`"
                                            class="mt-1 block text-sm break-all text-gray-600 hover:text-indigo-600 hover:underline"
                                        >
                                            {{ account.email }}
                                        </a>
                                    </div>
                                </div>
                                <dl
                                    class="mt-4 border-t border-gray-200 pt-3 text-sm"
                                >
                                    <div class="flex justify-between gap-4">
                                        <dt class="font-medium text-gray-500">
                                            登録日
                                        </dt>
                                        <dd class="text-gray-700">
                                            {{ formatDate(account.created_at) }}
                                        </dd>
                                    </div>
                                </dl>
                            </article>
                        </div>

                        <div
                            class="flex flex-col gap-4 border-t border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                        >
                            <p class="text-sm text-gray-600">
                                全{{ accounts.total }}件中
                                {{ accounts.from }}〜{{ accounts.to }}件を表示
                            </p>
                            <nav
                                v-if="accounts.last_page > 1"
                                class="flex flex-wrap gap-1"
                                aria-label="アカウント一覧のページネーション"
                            >
                                <template
                                    v-for="link in accounts.links"
                                    :key="link.label"
                                >
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-md border px-3 py-2 text-sm font-medium transition"
                                        :class="
                                            link.active
                                                ? 'border-indigo-600 bg-indigo-600 text-white'
                                                : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                                        "
                                        :aria-current="
                                            link.active ? 'page' : undefined
                                        "
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
            :show="showAccountModal"
            :closeable="!accountForm.processing"
            max-width="lg"
            aria-labelledby="account-form-title"
            @close="requestAccountModalClose"
        >
            <form @submit.prevent="submitAccountForm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h2
                        id="account-form-title"
                        class="text-lg font-semibold text-gray-900"
                    >
                        アカウント登録
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        システムにログインできる新しいアカウントを作成します。
                    </p>
                </div>

                <div class="space-y-6 px-6 py-5">
                    <div>
                        <InputLabel for="account-name">
                            名前
                            <span class="text-red-600">*</span>
                        </InputLabel>
                        <TextInput
                            id="account-name"
                            v-model="accountForm.name"
                            type="text"
                            maxlength="255"
                            autocomplete="name"
                            required
                            autofocus
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="accountForm.errors.name"
                        />
                    </div>

                    <div>
                        <InputLabel for="account-email">
                            メールアドレス
                            <span class="text-red-600">*</span>
                        </InputLabel>
                        <TextInput
                            id="account-email"
                            v-model="accountForm.email"
                            type="email"
                            maxlength="255"
                            autocomplete="username"
                            required
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="accountForm.errors.email"
                        />
                    </div>

                    <div>
                        <InputLabel for="account-password">
                            パスワード
                            <span class="text-red-600">*</span>
                        </InputLabel>
                        <TextInput
                            id="account-password"
                            v-model="accountForm.password"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="mt-1 block w-full"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            8文字以上で入力してください。
                        </p>
                        <InputError
                            class="mt-2"
                            :message="accountForm.errors.password"
                        />
                    </div>

                    <div>
                        <InputLabel for="account-password-confirmation">
                            パスワード（確認用）
                            <span class="text-red-600">*</span>
                        </InputLabel>
                        <TextInput
                            id="account-password-confirmation"
                            v-model="accountForm.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="accountForm.errors.password_confirmation"
                        />
                    </div>
                </div>

                <div
                    class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4"
                >
                    <SecondaryButton
                        type="button"
                        :disabled="accountForm.processing"
                        @click="requestAccountModalClose"
                    >
                        キャンセル
                    </SecondaryButton>
                    <PrimaryButton
                        type="submit"
                        :disabled="accountForm.processing"
                        :class="{ 'opacity-25': accountForm.processing }"
                    >
                        {{ accountForm.processing ? '登録中…' : '登録する' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
