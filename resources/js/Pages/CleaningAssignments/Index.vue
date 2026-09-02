<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    selectedDate: {
        type: String,
        required: true,
    },
    existingAssignments: {
        type: /** @type {import('vue').PropType<Array<AssignmentRole>>} */ (
            Array
        ),
        required: true,
    },
    activeMembers: {
        type: /** @type {import('vue').PropType<Array<{id: number, name: string}>>} */ (
            Array
        ),
        required: true,
    },
    hasActiveRoles: {
        type: Boolean,
        required: true,
    },
});

/**
 * @typedef {{member_id: number, name: string}} AssignmentMember
 * @typedef {{
 *   cleaning_role_id: number,
 *   name: string,
 *   required_member_count: number,
 *   assignments: Array<AssignmentMember>,
 *   assigned_member_count: number,
 *   shortage_count: number,
 * }} AssignmentRole
 * @typedef {{
 *   roles: Array<AssignmentRole>,
 *   assigned_member_count: number,
 *   required_member_count: number,
 *   shortage_count: number,
 * }} AssignmentPreview
 */

const page = usePage();
const date = ref(props.selectedDate);
const excludedMemberIds = ref(/** @type {number[]} */ ([]));
const preview = ref(/** @type {AssignmentPreview|null} */ (null));
const previewLoading = ref(false);
const previewError = ref('');
const confirmForm = useForm({
    assignment_date: props.selectedDate,
    excluded_member_ids: /** @type {number[]} */ ([]),
    assignments: /** @type {Array<{
        member_id: number,
        cleaning_role_id: number,
    }>} */ ([]),
});

const successMessage = computed(() => page.flash.success ?? '');
const hasExistingAssignments = computed(
    () => props.existingAssignments.length > 0,
);
const isDisplayedDate = computed(() => date.value === props.selectedDate);
const canPreview = computed(
    () =>
        isDisplayedDate.value &&
        !hasExistingAssignments.value &&
        props.hasActiveRoles &&
        !previewLoading.value,
);
const existingAssignedCount = computed(() =>
    props.existingAssignments.reduce(
        (total, role) => total + role.assigned_member_count,
        0,
    ),
);
const existingRequiredCount = computed(() =>
    props.existingAssignments.reduce(
        (total, role) => total + role.required_member_count,
        0,
    ),
);

watch(
    excludedMemberIds,
    () => {
        preview.value = null;
        previewError.value = '';
        confirmForm.clearErrors();
    },
    { deep: true },
);

watch(date, () => {
    preview.value = null;
    previewError.value = '';
    confirmForm.clearErrors();
});

const showSelectedDate = () => {
    if (!date.value || isDisplayedDate.value) {
        return;
    }

    router.get(
        route('cleaning-assignments.index'),
        { date: date.value },
        { preserveScroll: true },
    );
};

const generatePreview = async () => {
    if (!canPreview.value) {
        return;
    }

    previewLoading.value = true;
    previewError.value = '';
    confirmForm.clearErrors();

    try {
        const response = await axios.post(
            route('cleaning-assignments.preview'),
            {
                assignment_date: props.selectedDate,
                excluded_member_ids: excludedMemberIds.value,
            },
        );

        preview.value = response.data;
    } catch (error) {
        const errors = error.response?.data?.errors;
        previewError.value = errors
            ? Object.values(errors).flat()[0]
            : '自動選択に失敗しました。時間をおいて再度お試しください。';
    } finally {
        previewLoading.value = false;
    }
};

const confirmAssignments = () => {
    if (!preview.value || preview.value.assigned_member_count === 0) {
        return;
    }

    confirmForm.assignment_date = props.selectedDate;
    confirmForm.excluded_member_ids = [...excludedMemberIds.value];
    confirmForm.assignments = preview.value.roles.flatMap((role) =>
        role.assignments.map((assignment) => ({
            member_id: assignment.member_id,
            cleaning_role_id: role.cleaning_role_id,
        })),
    );

    confirmForm.post(route('cleaning-assignments.store'), {
        preserveScroll: true,
        onError: () => {
            if (confirmForm.errors.assignments) {
                previewError.value = confirmForm.errors.assignments;
                preview.value = null;
            }
        },
    });
};

const formatDate = (value) => {
    const [year, month, day] = value.split('-');

    return `${year}年${Number(month)}月${Number(day)}日`;
};
</script>

<template>
    <Head title="掃除当番" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    掃除当番
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    担当可能な掃除と過去の担当回数をもとに、自動で当番を選択します。
                </p>
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

                <section
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="space-y-5 p-6">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                対象日
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                確定済みの日付は表示のみとなり、上書きされません。
                            </p>
                        </div>

                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:items-end"
                        >
                            <label class="block w-full sm:max-w-xs">
                                <span class="text-sm font-medium text-gray-700">
                                    日付
                                </span>
                                <input
                                    v-model="date"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @keyup.enter="showSelectedDate"
                                />
                            </label>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!date || isDisplayedDate"
                                @click="showSelectedDate"
                            >
                                この日を表示
                            </button>
                        </div>

                        <p
                            v-if="!isDisplayedDate"
                            class="text-sm font-medium text-amber-700"
                        >
                            「この日を表示」を押して、選択した日付の状態を確認してください。
                        </p>
                    </div>
                </section>

                <section
                    v-if="hasExistingAssignments"
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="border-b border-gray-200 px-6 py-5">
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <h3
                                    class="text-base font-semibold text-gray-900"
                                >
                                    {{ formatDate(selectedDate) }}の確定済み当番
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ existingAssignedCount }}名 / 必要{{
                                        existingRequiredCount
                                    }}名
                                </p>
                            </div>
                            <span
                                class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800"
                            >
                                確定済み
                            </span>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-200">
                        <div
                            v-for="role in existingAssignments"
                            :key="role.cleaning_role_id"
                            class="grid gap-3 px-6 py-5 sm:grid-cols-[minmax(10rem,1fr)_2fr_auto] sm:items-center"
                        >
                            <div>
                                <p class="font-semibold text-gray-900">
                                    {{ role.name }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    必要{{ role.required_member_count }}名
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="member in role.assignments"
                                    :key="member.member_id"
                                    class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-800"
                                >
                                    {{ member.name }}
                                </span>
                            </div>
                            <span
                                v-if="role.shortage_count > 0"
                                class="text-sm font-semibold text-amber-700"
                            >
                                {{ role.shortage_count }}名不足
                            </span>
                        </div>
                    </div>
                </section>

                <template v-else>
                    <section
                        class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                    >
                        <div class="space-y-5 p-6">
                            <div>
                                <h3
                                    class="text-base font-semibold text-gray-900"
                                >
                                    当日除外するメンバー
                                    <span class="font-normal text-gray-500"
                                        >（任意）</span
                                    >
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    休暇や欠席など、この日の当番候補から外すメンバーを選択します。
                                </p>
                            </div>

                            <div
                                v-if="activeMembers.length > 0"
                                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                <label
                                    v-for="member in activeMembers"
                                    :key="member.id"
                                    class="flex cursor-pointer items-center gap-3 rounded-md border border-gray-200 px-4 py-3 transition hover:bg-gray-50"
                                >
                                    <input
                                        v-model="excludedMemberIds"
                                        type="checkbox"
                                        :value="member.id"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    />
                                    <span
                                        class="text-sm font-medium text-gray-800"
                                    >
                                        {{ member.name }}
                                    </span>
                                </label>
                            </div>
                            <p v-else class="text-sm text-amber-700">
                                有効なメンバーが登録されていません。
                            </p>

                            <div
                                class="flex flex-col gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:items-center"
                            >
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="!canPreview"
                                    @click="generatePreview"
                                >
                                    {{
                                        previewLoading
                                            ? '選択中...'
                                            : '自動選択'
                                    }}
                                </button>
                                <p
                                    v-if="!hasActiveRoles"
                                    class="text-sm text-amber-700"
                                >
                                    有効な掃除役割が登録されていません。
                                </p>
                            </div>

                            <p
                                v-if="previewError"
                                role="alert"
                                class="text-sm font-medium text-red-600"
                            >
                                {{ previewError }}
                            </p>
                        </div>
                    </section>

                    <section
                        v-if="preview"
                        class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                    >
                        <div class="border-b border-gray-200 px-6 py-5">
                            <div
                                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <h3
                                        class="text-base font-semibold text-gray-900"
                                    >
                                        自動選択結果
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ preview.assigned_member_count }}名 /
                                        必要{{
                                            preview.required_member_count
                                        }}名
                                    </p>
                                </div>
                                <span
                                    v-if="preview.shortage_count > 0"
                                    class="inline-flex w-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800"
                                >
                                    合計{{ preview.shortage_count }}名不足
                                </span>
                                <span
                                    v-else
                                    class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800"
                                >
                                    必要人数を充足
                                </span>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-200">
                            <div
                                v-for="role in preview.roles"
                                :key="role.cleaning_role_id"
                                class="grid gap-3 px-6 py-5 sm:grid-cols-[minmax(10rem,1fr)_2fr_auto] sm:items-center"
                            >
                                <div>
                                    <p class="font-semibold text-gray-900">
                                        {{ role.name }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        必要{{ role.required_member_count }}名
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="member in role.assignments"
                                        :key="member.member_id"
                                        class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-800"
                                    >
                                        {{ member.name }}
                                    </span>
                                    <span
                                        v-if="role.assignments.length === 0"
                                        class="text-sm text-gray-400"
                                    >
                                        担当者なし
                                    </span>
                                </div>
                                <span
                                    v-if="role.shortage_count > 0"
                                    class="text-sm font-semibold text-amber-700"
                                >
                                    {{ role.shortage_count }}名不足
                                </span>
                            </div>
                        </div>

                        <div
                            class="space-y-3 border-t border-gray-200 bg-gray-50 px-6 py-5"
                        >
                            <p
                                v-if="preview.shortage_count > 0"
                                class="text-sm text-amber-800"
                            >
                                担当可能者が足りない役割があります。不足を含む現在の結果で確定できます。
                            </p>
                            <InputError
                                :message="confirmForm.errors.assignments"
                            />
                            <InputError
                                :message="confirmForm.errors.assignment_date"
                            />
                            <PrimaryButton
                                :disabled="
                                    confirmForm.processing ||
                                    preview.assigned_member_count === 0
                                "
                                @click="confirmAssignments"
                            >
                                {{
                                    confirmForm.processing
                                        ? '確定中...'
                                        : 'この内容で確定'
                                }}
                            </PrimaryButton>
                        </div>
                    </section>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
