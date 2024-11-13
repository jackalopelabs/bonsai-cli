<div x-data="{ open: false }" class="accordion">
 <button @click="open = !open" class="accordion-toggle">
     Accordion Title
 </button>
 <div x-show="open" class="accordion-content">
     <!-- Accordion content goes here -->
 </div>
</div>
