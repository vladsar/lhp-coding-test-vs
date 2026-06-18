<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import type { ButtonVariants } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { EventCard } from '@/types/events';

const props = withDefaults(
    defineProps<{
        event: EventCard;
        buttonVariant?: ButtonVariants['variant'];
        buttonSize?: ButtonVariants['size'];
    }>(),
    {
        buttonVariant: 'default',
        buttonSize: 'default',
    },
);

const open = ref(false);

function onSuccess() {
    open.value = false;
    toast.success(`You're on the list for ${props.event.title}.`);
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button :variant="buttonVariant" :size="buttonSize"
                >Register</Button
            >
        </DialogTrigger>
        <DialogContent>
            <Form
                :action="`/events/${event.id}/attendees`"
                method="post"
                reset-on-success
                class="space-y-6"
                @success="onSuccess"
                v-slot="{ errors, processing }"
            >
                <DialogHeader class="space-y-2">
                    <DialogTitle>Register for {{ event.title }}</DialogTitle>
                    <DialogDescription>
                        Add your details and we'll email you a confirmation,
                        then a reminder as the event approaches.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="attendee-name">Name</Label>
                    <Input
                        id="attendee-name"
                        name="name"
                        autocomplete="name"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="attendee-email">Email</Label>
                    <Input
                        id="attendee-email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        required
                    />
                    <InputError :message="errors.email" />
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary" type="button"
                            >Cancel</Button
                        >
                    </DialogClose>
                    <Button type="submit" :disabled="processing"
                        >Confirm registration</Button
                    >
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
