<script setup>
import { computed, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    selectedDate: {
        type: String,
        required: true,
    },
    assignmentCount: {
        type: Number,
        required: true,
    },
});

const date = ref(props.selectedDate);
const isDisplayedDate = computed(() => date.value === props.selectedDate);
const hasAssignments = computed(() => props.assignmentCount > 0);
const canOutput = computed(() => isDisplayedDate.value && hasAssignments.value);
const previewUrl = computed(() =>
    route('pdf.preview', { date: props.selectedDate }),
);
const downloadUrl = computed(() =>
    route('pdf.download', { date: props.selectedDate }),
);

watch(
    () => props.selectedDate,
    (selectedDate) => {
        date.value = selectedDate;
    },
);

const showSelectedDate = () => {
    if (!date.value || isDisplayedDate.value) {
        return;
    }

    router.get(
        route('pdf.index'),
        { date: date.value },
        { preserveScroll: true },
    );
};

const formatDate = (value) => {
    const [year, month, day] = value.split('-');

    return `${year}年${Number(month)}月${Number(day)}日`;
};
</script>

<template>
    <Head title="PDF出力" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    PDF出力
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    確定済みの掃除当番を日付ごとにPDFへ出力します。
                </p>
            </div>
        </template>

        <div class="py-8 sm:py-12">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="space-y-5 p-6">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                対象日
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                出力する掃除当番の日付を選択してください。
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
                            「この日を表示」を押して、選択した日付の確定状況を確認してください。
                        </p>
                    </div>
                </section>

                <section
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="space-y-5 p-6">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                掃除当番表
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ formatDate(selectedDate) }}の確定済みデータ
                            </p>
                        </div>

                        <div
                            v-if="hasAssignments"
                            role="status"
                            class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                        >
                            {{ assignmentCount }}名分の掃除当番を出力できます。
                        </div>
                        <div
                            v-else
                            role="status"
                            class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                        >
                            この対象日の確定済み掃除当番はありません。
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <a
                                :href="canOutput ? previewUrl : undefined"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                                :class="{
                                    'pointer-events-none opacity-50':
                                        !canOutput,
                                }"
                                :aria-disabled="!canOutput"
                            >
                                PDFをプレビュー
                            </a>
                            <a
                                :href="canOutput ? downloadUrl : undefined"
                                class="inline-flex items-center justify-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                                :class="{
                                    'pointer-events-none opacity-50':
                                        !canOutput,
                                }"
                                :aria-disabled="!canOutput"
                            >
                                PDFをダウンロード
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
