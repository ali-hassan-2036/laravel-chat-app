<template>
    <img
      :src="src"
      :class="[
        'rounded-full shrink-0',
        sizeClasses[size]
      ]"
      :alt="alt"
    />
  </template>
  
  <script setup>
  const props = defineProps({
    src: {
      type: String,
      required: true
    },
    alt: {
      type: String,
      default: 'User avatar'
    },
    size: {
      type: String,
      default: 'md',
      validator: (value) => ['sm', 'md', 'lg'].includes(value)
    }
  })
  
  const sizeClasses = {
    sm: 'w-6 h-6',
    md: 'w-8 h-8',
    lg: 'w-12 h-12'
  }
  </script>
  