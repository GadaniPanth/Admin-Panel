import { Component, OnInit } from '@angular/core';
import { AdminService } from '../_services/admin.service';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastrService } from "ngx-toastr";

@Component({
  selector: 'app-add-forms',
  templateUrl: './add-forms.component.html',
  styleUrls: ['./add-forms.component.css']
})
export class AddFormsComponent implements OnInit {

  // addedFieldArray: any[] = [{}];
  // label: string | null;
  // type: string | null;
  // options: string[] | null;
  // field: { label: string | null; type: string | null; options: string[] | null };
  errorMessage: string = '';
  formTitle: string = 'Create Form';
  formBtn: string = 'Create';

  formModel = {
    title: '',
    addedFieldArray: [{ label: '', label_type: '', options: [] }]
  };
  isLoading: boolean = false;
  // fieldCount: number = 1;

  inputTypes: string[] = [
    // 'button',
    'checkbox',
    // 'color',
    'date',
    // 'datetime-local',
    // 'email',
    'file',
    // 'hidden',
    // 'image',
    // 'month',
    // 'number',
    // 'password',
    'radio',
    // 'range',
    // 'reset',
    // 'search',
    // 'submit',
    // 'tel',
    'text',
    'textarea',
    // 'time',
    // 'url',
    // 'week'
  ];

  optionEnableTypes: string[] = [
    'checkbox',
    'radio',
  ]

  selectedType: string;

  formId: number = null;

  constructor(private adminService: AdminService, private router: Router, private toastr: ToastrService, private route: ActivatedRoute) {
    this.route.params.subscribe(params => {
          // console.log(params)
          this.formId = params["id"];
      });
  }

  trackByFn(index: number, item: any) {
    return item.id;
  }

  ngOnInit() {
    if(this.formId){
        this.isLoading = true;
        this.formTitle = 'Edit Form';
        this.formBtn = 'Edit';

        this.adminService.getForm({form_id: this.formId}).subscribe((res)=>{
          // console.log(res.form);
          this.formModel.title = null;
          this.formModel.title = res.form.form_title;
          this.formModel.addedFieldArray = [];
          res.form.others.forEach((field: any) => {
              // console.log(field.options);
              if(!field.options){
                // console.log('no options');
                field.options = [];
              }
              this.formModel.addedFieldArray.push(field);
          });

          this.isLoading = false;
        });
        // console.log(this.formModel);
      }
  }

  onTypeSelect(index: number) {
    const selectedType = this.formModel.addedFieldArray[index].label_type;
    if(this.optionEnableTypes.includes(selectedType) && this.formModel.addedFieldArray[index].options.length <= 0){
      this.formModel.addedFieldArray[index].options.push('');
    }else if(!this.optionEnableTypes.includes(selectedType) ){
      this.formModel.addedFieldArray[index].options = [];
    }
  }

  addField() {
    this.formModel.addedFieldArray.push({ label: '', label_type: '', options: [] });
  }

  removeField(index: number) {
    this.formModel.addedFieldArray.splice(index, 1);
  }

  addOption(fieldIndex: number) {
    this.formModel.addedFieldArray[fieldIndex].options.push('');
  }

  removeOption(fieldIndex: number, optionIndex: number) {
    this.formModel.addedFieldArray[fieldIndex].options.splice(optionIndex, 1);
  }

  submitForm(form: any) {
    if (form.invalid) {
      return;
    }

    this.formModel.addedFieldArray.forEach((field) => {
      if (Array.isArray(field.options) && field.options.length <= 0) {
        delete field.options;
      }
    });

    const formData = new FormData();
    formData.append('form_title', this.formModel.title);
    formData.append('others', JSON.stringify(this.formModel.addedFieldArray));

    // console.log('Form Title:', this.formModel.title);
    // console.log('Fields:', this.formModel.addedFieldArray);
    // for (let pair of (formData as any).entries()) {
    //   console.log(`${pair[0]}:`, pair[1]);
    // }
    
    if(this.formId){
      // console.log(this.formId);
      formData.append('form_id', `${this.formId}`);
      
      for (let pair of (formData as any).entries()) {
        console.log(`${pair[0]}:`, pair[1]);
      }
      
      this.adminService.editForm(formData).subscribe((res)=>{
          if(res.success){
          this.toastr.success(res.message);
          this.router.navigate(['/list-form']);
          this.formModel = {
            title: '',
            addedFieldArray: [{ label: '', label_type: '', options: [] }]
          };
        }else {
          this.toastr.error(res.message);
        }
      })
    }else {
      this.adminService.createForm(formData).subscribe((res)=>{
        if(res.success){
          this.toastr.success(res.message);
          this.router.navigate(['/list-form']);
          this.formModel = {
            title: '',
            addedFieldArray: [{ label: '', label_type: '', options: [] }]
          };
        }else {
          this.toastr.error(res.message);
        }
      })
    }

  }
}
