import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { AdminService } from 'src/app/_services/admin.service';
import { ExcelService } from 'src/app/_services/excel.service';
import { PagerService } from 'src/app/_services/pager-service';

@Component({
  selector: 'app-list-dynamic-form',
  templateUrl: './list-dynamic-form.component.html',
  styleUrls: ['./list-dynamic-form.component.css']
})
export class ListDynamicFormComponent implements OnInit {

  constructor(public adminService: AdminService, private router: Router, private toastr: ToastrService, private excelService: ExcelService, private pagerService: PagerService) { }

  ngOnInit() {
    this.formList();

    // p

    this.permissionObj = this.adminService.userpermissions;

    for (const key in this.permissionObj) {
      if (this.permissionObj[key]['slug'] == 'list-form') {
        for (const k in this.permissionObj[key]) {
          if (this.permissionObj[key][k] == 1) {
            this.permissions.push(k);
          }
        }
      }
    }


    // p
    // this.formType();
  }

  permissions: any = [];
  permissionObj: any;
  // isDelete: boolean= true;

  formObj: any = {
    page: 1,
    limit: 10,
    search: '',
    status: null,
    total: 0,
    sort_by: '',
    sort_order: '',
    from_date: '',
    to_date: '',
    order_by: '',
    order_type: '',
  }

  formCount: number = 0;

  form_List: any = [];
  form_list_type: any = [];

  isLoading: any = false;

  total_records: any = 0;

  formList() {
    this.isLoading = true;

    // const params = {
    //   search: this.formObj.search,
    //   page: this.formObj.page,
    //   limit: this.formObj.limit
    // };

    this.adminService.formList(this.formObj).subscribe((response: any) => {
      if (response.success == 1) {
        this.form_List = response.list;
        // console.log(this.form_List);
        this.total_records = response.total_records;
        this.formObj.total = response.total_records;
        this.formCount = response.total_records;
        if (this.form_List && this.form_List.length > 0) {
          this.setUsersPage(this.formObj.page, 0);
        }
        this.isLoading = false;
        this.form_list_type = [{ type: 'Active', status: 1 }, { type: 'Inactive', status: 0 }];
      } else {
        this.form_List = [];
        this.isLoading = false;
      }
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


  isToggled: boolean = true;
  // showActions = false;
  toggle(id, status): void {
    // console.log(status, "Status");
    // console.log(status, "Status");
    // console.log(id, "Id");
    // console.log(this.form_List);

    if (!this.permissions.includes('can_edit')) {
      return;
    }

    status = status == "1" ? "0" : "1";
    this.adminService.changeStatusForm({ form_id: id, is_active: status }).subscribe((res) => {
      if (res.success) {
        this.toastr.success(res.message);
        // this.formList();
        this.form_List.forEach((element: any) => {
          if (element.id == id) {
            // console.log(element.is_active);
            element.is_active = status;
            // console.log(element.is_active);
          }
        });
      } else {
        this.toastr.success(res.message);
      }
    })
  }

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
            const index = this.form_List.findIndex(item => item.id === this.deleteObj.form_id);
            if (index !== -1) {
              this.form_List.splice(index, 1);
            }
            this.deleteObj = {
              form_id: "",
            };
            this.isDelete = false;
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

  onEdit(id: number) {
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

  // excel

  exportList: any = [];
  exportAsXLSX(): void {
    var i = 1; let t = this;
    var obj = [];

    let headers = [];
    const payload = {
      limit: 1000000
    }
    this.adminService.formList(payload).subscribe((response: any) => {
      if (response.success == 1) {
        this.exportList = response.list;

        this.exportList.map(function (a: any) {
          let exportData = {};

          exportData['Sr.No'] = i++;
          exportData['Form Title'] = a.form_title;
          exportData['Status'] = a.is_active ? a.is_active : "";
          exportData['Created At'] = a.created_at_formated ? a.created_at_formated : "";
          exportData['Last Updated At'] = a.last_updated_at ? a.last_updated_at : "";
          // exportData['Closed At'] = a.closed_at ? a.closed_at : "";

          obj.push(exportData);
        });

        // this.excelService.exportAsExcelFile(obj, headers, "Categories");
        this.excelService.exportAsExcelFile(obj, headers, "Inquiry Forms")
      }
      else {
      }
    });
  }

  // Pagination
  public usersPager: any = [];
  setUsersPage(page: number, flag: number) {
    this.usersPager = this.pagerService.getPager(this.formObj.total, page, this.formObj.limit);
    this.formObj.page = this.usersPager.currentPage;

    this.router.navigate([], {
      queryParams: {
        page: this.formObj.page
      },
      queryParamsHandling: 'merge', // This keeps existing query params
    });

    if (flag == 1) {
      // this.getProductList();
      this.formList();
    }
  }
}
