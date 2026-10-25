<script setup>
import { onBeforeUnmount, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';

const props = defineProps({
    modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            link: { openOnClick: false, autolink: true },
        }),
        Image.configure({ inline: false, allowBase64: false }),
    ],
    editorProps: {
        attributes: {
            class: 'rich-editor__content article-body',
            'aria-label': 'Page content editor',
        },
    },
    onUpdate: ({ editor: activeEditor }) => emit('update:modelValue', activeEditor.getHTML()),
});

watch(
    () => props.modelValue,
    (value) => {
        if (editor.value && value !== editor.value.getHTML()) {
            editor.value.commands.setContent(value || '', false);
        }
    },
);

onBeforeUnmount(() => editor.value?.destroy());

function setLink() {
    const previous = editor.value.getAttributes('link').href || '';
    const url = window.prompt('Link URL', previous);
    if (url === null) return;
    if (url === '') editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
    else editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}
</script>

<template>
    <div class="rich-editor">
        <div v-if="editor" class="rich-editor__toolbar">
            <button type="button" :class="{ active: editor.isActive('bold') }" title="Bold" @click="editor.chain().focus().toggleBold().run()"><strong>B</strong></button>
            <button type="button" :class="{ active: editor.isActive('italic') }" title="Italic" @click="editor.chain().focus().toggleItalic().run()"><em>I</em></button>
            <button type="button" :class="{ active: editor.isActive('heading', { level: 2 }) }" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">H2</button>
            <button type="button" :class="{ active: editor.isActive('heading', { level: 3 }) }" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()">H3</button>
            <button type="button" :class="{ active: editor.isActive('bulletList') }" @click="editor.chain().focus().toggleBulletList().run()">• List</button>
            <button type="button" :class="{ active: editor.isActive('orderedList') }" @click="editor.chain().focus().toggleOrderedList().run()">1. List</button>
            <button type="button" :class="{ active: editor.isActive('blockquote') }" @click="editor.chain().focus().toggleBlockquote().run()">Quote</button>
            <button type="button" :class="{ active: editor.isActive('link') }" @click="setLink">Link</button>
            <button type="button" @click="editor.chain().focus().unsetAllMarks().clearNodes().run()">Clear</button>
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>
