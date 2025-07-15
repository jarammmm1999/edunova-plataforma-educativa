
import { Component, Input, Output, EventEmitter, SimpleChanges } from '@angular/core';
import { QuillModule } from 'ngx-quill';
import { FormControl, FormsModule } from '@angular/forms';

@Component({
  selector: 'app-editor-text',
  imports: [QuillModule,FormsModule],
  templateUrl: './editor-text.html',
  styleUrl: './editor-text.css'
})
export class EditorText {
  @Input() contenido: string = '';
  @Output() contenidoChange = new EventEmitter<string>();
  @Input() placeholder: string = 'Escribe aquí tu respuesta o comentario...';



  contenidoForm = new FormControl('');

 

modules = {
  toolbar: [
    [{ header: [1, 2, 3, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    ['blockquote', 'code-block'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    [{ script: 'sub' }, { script: 'super' }],
    [{ indent: '-1' }, { indent: '+1' }],
    [{ direction: 'rtl' }],
    [{ color: [] }, { background: [] }],
    [{ align: [] }],
    ['link', 'image', 'video'],
    ['clean']
  ]
};

   onContentChanged(event: any) {
    this.contenidoForm.setValue(this.contenido || '');
     this.contenidoForm.setValue(this.contenido || '', { emitEvent: false });
      this.contenidoForm.valueChanges.subscribe(value => {
      this.contenidoChange.emit(value || '');
    });
  }
}
