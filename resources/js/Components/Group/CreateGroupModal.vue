<!-- resources/js/Pages/Groups/Index.vue -->
<template>  
      <!-- Create Group Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
        <form @submit.prevent="createGroup" class="relative mx-auto p-5 border w-40-percent shadow-lg rounded-md bg-white">
          <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Group</h3>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Group Name</label>
              <input
                v-model="createForm.name"
                type="text"
                required
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Enter group name"
              >
            </div>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
              <textarea
                v-model="createForm.description"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                rows="3"
                placeholder="Enter group description"
              ></textarea>
            </div>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Group Image (Optional)</label>
              <input
                @change="handleImageUpload"
                type="file"
                accept="image/*"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
            </div>
            
            <div class="mb-4">
              <label class="flex items-center">
                <input
                  v-model="createForm.is_private"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                >
                <span class="ml-2 text-sm text-gray-600">Private Group</span>
              </label>
            </div>
            
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Max Members</label>
              <input
                v-model.number="createForm.max_members"
                type="number"
                min="2"
                max="1000"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
            </div>
            
            <div class="flex justify-end space-x-3">
              <button
                type="button"
                @click="$emit('update:showCreateModal', false)"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-full hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="processing"
                class="px-5 py-2.5 bg-gradient-to-r from-green-500 via-green-700 to-green-900 border-0 rounded-full font-bold text-sm text-white shadow-lg hover:from-green-600 hover:to-green-800 hover:scale-105 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-green-300"
              >
                {{ processing ? 'Creating...' : 'Create Group' }}
              </button>
            </div>
          </div>
        </form>
      </div>
  </template>
  
  <script setup>
  import { ref, reactive } from 'vue'
  import { router } from '@inertiajs/vue3'
  

  const emit = defineEmits(['update:showCreateModal'])

  const props = defineProps({
    showCreateModal: {
      type: Boolean,
      required: true
    }
  })
  
  const processing = ref(false)
  
  const createForm = reactive({
    name: '',
    description: '',
    image: null,
    is_private: false,
    max_members: 100
  })
  
  const handleImageUpload = (event) => {
    createForm.image = event.target.files[0]
  }
  
  const createGroup = () => {
    processing.value = true
    
    console.log('createForm', createForm);

    const formData = new FormData()
    formData.append('name', createForm.name)
    formData.append('description', createForm.description)
    formData.append('is_private', createForm.is_private ? 1 : 0)
    formData.append('max_members', createForm.max_members)

    if (createForm.image) {
        formData.append('image', createForm.image)
    }

    // Optional: log to verify
    for (let [key, value] of formData.entries()) {
        console.log(`${key}:`, value)
    }
    
    router.post(route('groups.store'), formData, {
      onSuccess: () => {
        createForm.name = ''
        createForm.description = ''
        createForm.image = null
        createForm.is_private = false
        createForm.max_members = 100
      },
      onFinish: () => {
        processing.value = false
        // Emit to close the modal
        emit('update:showCreateModal', false)
      }
    })
  }
  </script>

<style scoped>
.w-40-percent {
    min-width: 35%;
}
</style>