import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ToastrService } from "ngx-toastr";
import { AdminService } from 'src/app/_services/admin.service';

@Component({
  selector: 'app-add-dynamic-form',
  templateUrl: './add-dynamic-form.component.html',
  styleUrls: ['./add-dynamic-form.component.css']
})
export class AddDynamicFormComponent implements OnInit {

  
    // addedFieldArray: any[] = [{}];
    // label: string | null;
    // type: string | null;
    // options: string[] | null;
    // field: { label: string | null; type: string | null; options: string[] | null };
    errorMessage: string = '';
    
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
  
    constructor(private adminService: AdminService, private router: Router, private toastr: ToastrService) {
      // console.log(this.inputTypes);
      // console.log(this.formModel.addedFieldArray.length);
    }
  
    trackByFn(index: number, item: any) {
      return item.id;
    }
  
    ngOnInit() {
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
      for (let pair of (formData as any).entries()) {
        console.log(`${pair[0]}:`, pair[1]);
      }
  
      this.adminService.createForm(formData).subscribe((res)=>{
        if(res.success){
          this.toastr.success(res.message);
          this.router.navigate(['/dashboard']);
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
