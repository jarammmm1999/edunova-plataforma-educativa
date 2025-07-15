import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-button-submit',
  imports: [],
  standalone: true,
  templateUrl: './button-submit.html',
  styleUrl: './button-submit.css',
})
export class ButtonSubmit {
  @Input() text: string = '';
}
