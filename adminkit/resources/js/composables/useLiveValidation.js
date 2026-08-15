import { computed } from 'vue';

/**
 * Validasi cepat di UI untuk form Inertia (`useForm`).
 * Skema: { field: validatorFn } — validator mengembalikan pesan atau ''.
 * Pesan disimpan ke `form.errors` agar tampilan error seragam dengan error server.
 */
export function useLiveValidation(form, schema) {
    const fields = Object.keys(schema);

    const messageOf = (field) => schema[field](form[field], form);

    const validate = (field) => {
        const message = messageOf(field);
        if (message) form.setError(field, message);
        else form.clearErrors(field);

        return !message;
    };

    const validateAll = () => fields.map(validate).every(Boolean);

    const isValid = computed(() => fields.every((field) => !messageOf(field)));

    /** Jalankan `action` hanya bila seluruh kolom valid. */
    const submit = (action) => {
        if (validateAll()) action();
    };

    return { validate, validateAll, isValid, submit };
}
