import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ListDynamicFormComponent } from './list-dynamic-form.component';

describe('ListDynamicFormComponent', () => {
  let component: ListDynamicFormComponent;
  let fixture: ComponentFixture<ListDynamicFormComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ListDynamicFormComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ListDynamicFormComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
