import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { AdminService } from 'src/app/_services/admin.service';

@Component({
  selector: 'app-list-dynamic-form',
  templateUrl: './list-dynamic-form.component.html',
  styleUrls: ['./list-dynamic-form.component.css']
})
export class ListDynamicFormComponent implements OnInit {

  constructor(public adminService: AdminService,private router: Router, private toastr: ToastrService) { }

  ngOnInit() {
    this.formList();
    // this.formType();
  }

  // isDelete: boolean= true;

  formObj: any = {
    page: 1,
    limit: 10,
    search: '',
    status: '',
    total: 0,
    sort_by: '',
    sort_order: '',
    from_date: '',
    to_date: '',
    order_by: '',
    order_type: '',
  }

  form_List: any = [];

  isLoading: any = false;

  formList() {
    this.isLoading = true;

    const params = {
      search: this.formObj.search,
      page: this.formObj.page,
      limit: this.formObj.limit
    };
    
    this.adminService.formList(params).subscribe((response: any) => {
      if (response.success == 1) {
        this.form_List = response.list;
        // console.log(this.form_List);
        this.formObj.total = response.total;
      } else {
        this.form_List = [];
      }
      this.isLoading = false;
    });
  }

  // nextPage() {
  //   if (this.formObj.page * this.formObj.limit < this.formObj.total) {
  //     this.formObj.page++;
  //     this.formList();
  //   }
  // }

  // prevPage() {
  //   if (this.formObj.page > 1) {
  //     this.formObj.page--;
  //     this.formList();
  //   }
  // }

  onFilter() {
    this.formObj.page = 1;
    this.formList();
  }

  onSearchChange() {
    this.formObj.page = 1;
    this.formList();

    // this.searchSubject.next(this.categoryObj.search); // Trigger search with debounce
  }

  // onDelete(id: number){
  //   this.adminService.deleteForm(id).subscribe((res)=>{
  //     if(res.success){
  //         this.toastr.success(res.message);
  //         this.formList();
  //       }else {
  //         this.toastr.success(res.message);
  //         this.formList();
  //     }
  //   })
  // }


 // delete

  public deleteObj: any = {
    form_id: "",
  };


  //Inquiry Form Submit Funccion
  public isDelete: boolean = false;
  confirmDelete() {
    if (this.isDelete == false) {
      this.isDelete = true;

      this.adminService
        .deleteForm(this.deleteObj)
        .subscribe((response: any) => {
          if (response.success == 1) {
            this.deleteObj = {
              form_id: "",
            };
            this.isDelete = false;
            this.formList();
            this.toastr.success(response.message);
          } else {
            this.isDelete = false;
            // this.toastr.error(response.message);
          }
        });

      this.isDelete = false;
    }
  }

  deletepopup(id: any) {
    if (this.deleteObj.form_id == id) {
      this.deleteObj.form_id = "";
    } else {
      this.deleteObj.form_id = id;
    }
  }
  cancelCahnge() {
    this.deleteObj.form_id = "";
  }

  onEdit(id: number){
    this.router.navigate([`/edit-form/${id}`]);
  }

  // form_type_list: any = [];

  // formType() {
  //   this.adminService.form_typelist({}).subscribe((response: any) => {
  //     if (response.success == 1) {
  //       this.form_type_list = response.list;
  //     }
  //     else {
  //       this.form_type_list = [];
  //     }
  //   })
  // }

  // isOverlayOpen: boolean = false;
  // formDetailObj: any = {}
  // toggleDetail(data: any) {
  //   this.formDetailObj = data;
  //   // console.log(data);
  //   // console.log(this.formDetailObj);

  //   this.isOverlayOpen = !this.isOverlayOpen;

  // }

  // closeDetail() {
  //   this.isOverlayOpen = false;
  // }
}
