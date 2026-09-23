<script setup>
import { useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({ rooms: Array, activeBranch: Object });
const form = useForm({
    room_id: "",
    assigned_to: "",
    task_type: "repair",
    priority: "normal",
    due_date: new Date().toISOString().split("T")[0],
    notes: "",
});
function store() {
    form.post(route("maintenance.store"));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header
            ><h2 class="font-semibold text-xl text-gray-800 leading-tight">
                New Maintenance Task
            </h2></template
        >
        <div class="py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form
                    @submit.prevent="store"
                    class="rounded-lg bg-white p-8 shadow-sm"
                >
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="room_id" value="Room" />
                            <select
                                id="room_id"
                                v-model="form.room_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select room...</option>
                                <option
                                    v-for="room in rooms"
                                    :key="room.id"
                                    :value="room.id"
                                >
                                    {{ room.room_number }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.room_id"
                            />
                        </div>
                        <div>
                            <InputLabel for="task_type" value="Task Type" />
                            <select
                                id="task_type"
                                v-model="form.task_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="repair">Repair</option>
                                <option value="plumbing">Plumbing</option>
                                <option value="electrical">Electrical</option>
                                <option value="carpentry">Carpentry</option>
                                <option value="painting">Painting</option>
                                <option value="general">General</option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.task_type"
                            />
                        </div>
                        <div>
                            <InputLabel for="assigned_to" value="Assigned To" />
                            <TextInput
                                id="assigned_to"
                                v-model="form.assigned_to"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Staff name..."
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.assigned_to"
                            />
                        </div>
                        <div>
                            <InputLabel for="priority" value="Priority" />
                            <select
                                id="priority"
                                v-model="form.priority"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.priority"
                            />
                        </div>
                        <div>
                            <InputLabel for="due_date" value="Due Date" />
                            <TextInput
                                id="due_date"
                                v-model="form.due_date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.due_date"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="notes" value="Notes" />
                            <TextAreaInput
                                id="notes"
                                v-model="form.notes"
                                class="mt-1 block w-full"
                                rows="3"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.notes"
                            />
                        </div>
                    </div>
                    <div class="mt-6 flex items-center justify-end gap-4">
                        <PrimaryButton :disabled="form.processing"
                            >Create Task</PrimaryButton
                        >
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
