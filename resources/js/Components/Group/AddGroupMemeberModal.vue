<template>
  <div v-if="openModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative">
      <h2 class="text-lg font-semibold mb-4">Add Group Members</h2>
      <button
        class="absolute top-2 right-2 text-gray-400 hover:text-gray-600"
        @click="closeModal"
        aria-label="Close"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
      <div>
        <div class="mb-4">
          <input
            v-model="search"
            type="text"
            placeholder="Search users..."
            class="w-full border rounded px-3 py-2 focus:outline-none focus:ring"
          />
        </div>
        <div class="max-h-64 overflow-y-auto border rounded mb-4">
          <template v-if="filteredUsers.length">
            <div
              v-for="user in filteredUsers"
              :key="user.id"
              class="flex items-center px-3 py-2 hover:bg-gray-50"
            >
              <input
                type="checkbox"
                :value="user.id"
                v-model="selectedUserIds"
                :id="'user-' + user.id"
                class="mr-3"
              />
              <label :for="'user-' + user.id" class="flex items-center cursor-pointer w-full">
                <img
                  v-if="user.avatar"
                  :src="user.avatar"
                  :alt="user.name"
                  class="w-8 h-8 rounded-full object-cover mr-3"
                >
                <div
                  v-else
                  class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center mr-3"
                >
                  <span class="text-gray-600 font-semibold">{{ user.name.charAt(0).toUpperCase() }}</span>
                </div>
                <span class="text-gray-800">{{ user.name }}</span>
                <span v-if="user.is_member" class="ml-auto text-xs text-green-500">Already in group</span>
              </label>
            </div>
          </template>
          <div v-else class="text-gray-500 px-3 py-4 text-center">No users found.</div>
        </div>
        <div class="flex justify-end">
          <button
            class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded mr-2"
            @click="closeModal"
            :disabled="submitting"
          >
            Cancel
          </button>
          <button
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"
            :disabled="selectedUserIds.length === 0 || submitting"
            @click="submit"
          >
            <span v-if="submitting">Adding...</span>
            <span v-else>Add Members</span>
          </button>
        </div>
        <div v-if="error" class="text-red-500 mt-2 text-sm">{{ error }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    openModal: {
        type: Boolean,
        default: false
    },
    groupId: {
        type: [String, Number],
        required: true
    },
    // Optionally, pass current group members to filter out
    currentMemberIds: {
        type: Array,
        default: () => []
    },
    users: {
        type:Array,
        default: () => []
    }
})

const emit = defineEmits(['close', 'added'])

const submitting = ref(false)
const error = ref('')
const selectedUserIds = ref([])
const search = ref('')

// Compute users with is_member property
const usersWithMembership = computed(() => {
  return props.users.map(user => ({
    ...user,
    is_member: props.currentMemberIds.includes(user.id)
  }))
})

// Filtered users: not already in group and match search
const filteredUsers = computed(() => {
  let list = usersWithMembership.value.filter(u => !u.is_member)
  if (search.value.trim()) {
    const s = search.value.trim().toLowerCase()
    list = list.filter(u => u.name.toLowerCase().includes(s))
  }
  return list
})

const closeModal = () => {
  emit('close')
  resetState()
}

const resetState = () => {
  selectedUserIds.value = []
  search.value = ''
  error.value = ''
  submitting.value = false
}

watch(
  () => props.openModal,
  (val) => {
    if (!val) {
      resetState()
    }
  }
)

const submit = async () => {
  if (!selectedUserIds.value.length) return
  submitting.value = true
  error.value = ''
  try {
    await router.post(
      route('group-members.store', props.groupId),
      { user_ids: selectedUserIds.value },
      {
        preserveScroll: true,
        onSuccess: () => {
          emit('added', selectedUserIds.value)
          closeModal()
        },
        onError: (errors) => {
          error.value = errors.user_ids || 'Failed to add members.'
        },
        onFinish: () => {
          submitting.value = false
        }
      }
    )
  } catch (e) {
    error.value = 'Failed to add members.'
    submitting.value = false
  }
}
</script>
