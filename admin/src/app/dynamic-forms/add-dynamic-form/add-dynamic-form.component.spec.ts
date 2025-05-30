import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AddDynamicFormComponent } from './add-dynamic-form.component';

describe('AddDynamicFormComponent', () => {
  let component: AddDynamicFormComponent;
  let fixture: ComponentFixture<AddDynamicFormComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AddDynamicFormComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AddDynamicFormComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
