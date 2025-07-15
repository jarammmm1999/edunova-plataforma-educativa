import { CommonModule } from '@angular/common';
import {
  Component,
  EventEmitter,
  forwardRef,
  Input,
  Output,
} from '@angular/core';
import { FormsModule } from '@angular/forms';
import {
  ControlValueAccessor,
  NG_VALUE_ACCESSOR,
} from '@angular/forms';

@Component({
  selector: 'app-buscador',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './buscador.html',
  styleUrl: './buscador.css',
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => Buscador),
      multi: true,
    },
  ],
})
export class Buscador implements ControlValueAccessor {
  @Input() placeholder: string = 'Buscar...';
  @Output() buscar = new EventEmitter<string>();

  valor: string = '';
  mostrarBuscador: boolean = false;

  // ControlValueAccessor
  onChange = (_: any) => {};
  onTouched = () => {};

  writeValue(value: string): void {
    this.valor = value;
  }

  registerOnChange(fn: any): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: any): void {
    this.onTouched = fn;
  }

  // Cuando el usuario escribe
  onInputChange(event: any): void {
    this.valor = event.target.value;
    this.onChange(this.valor);
    this.buscar.emit(this.valor);
  }
}
