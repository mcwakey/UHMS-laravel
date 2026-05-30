<template>
    <slot v-if="allowed" />
    <slot v-else name="fallback" />
</template>

<script setup>
import { computed } from 'vue';
import { usePermissions } from '@/Composables/usePermissions';

/**
 * Conditional render based on permission / role / module gate.
 *
 *   <Can permission="patients.delete">
 *     <button>Delete</button>
 *   </Can>
 *
 *   <Can :permission="['patients.edit','patients.create']">  <!-- any-of -->
 *   <Can :all="['patients.view','patients.edit']">           <!-- all-of  -->
 *   <Can role="Doctor">
 *   <Can module="pharmacy">
 *
 * Use the `fallback` slot to render alternative content when the gate fails:
 *
 *   <Can permission="patients.delete">
 *     <button>Delete</button>
 *     <template #fallback><span class="text-muted">No access</span></template>
 *   </Can>
 */
const props = defineProps({
    permission: { type: [String, Array], default: null },
    all:        { type: Array,            default: null },
    role:       { type: [String, Array], default: null },
    module:     { type: String,           default: null },
});

const { can, canAll, hasRole, moduleEnabled } = usePermissions();

const allowed = computed(() => {
    if (props.permission && !can(props.permission))   return false;
    if (props.all        && !canAll(props.all))       return false;
    if (props.role       && !hasRole(props.role))     return false;
    if (props.module     && !moduleEnabled(props.module)) return false;
    return true;
});
</script>
